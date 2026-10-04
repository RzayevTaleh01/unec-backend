<?php

namespace App\Services;

use App\Exceptions\WorkflowException;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\EditorialDecision;
use App\Models\Issue;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ArticleSubmitted;
use App\Notifications\DecisionMade;
use App\Notifications\ReviewAssigned;
use App\Notifications\ReviewCompleted;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Single place for every status change of a submission:
 *
 *   draft ──submit──▶ submitted ──assign reviewer──▶ in_review
 *   submitted/in_review ──decide──▶ accepted | revisions | rejected
 *   revisions ──author uploads revision──▶ submitted
 *   accepted ──publish (into an issue)──▶ published
 */
class SubmissionWorkflow
{
    /** decision => [allowed current statuses, resulting status] */
    private const TRANSITIONS = [
        'accept' => [['submitted', 'in_review'], 'accepted'],
        'revisions' => [['submitted', 'in_review'], 'revisions'],
        'reject' => [['submitted', 'in_review', 'accepted'], 'rejected'],
        'publish' => [['accepted'], 'published'],
    ];

    /** Decisions an editor may take while the article is in its current status. */
    public function allowedDecisions(Article $article): array
    {
        return array_keys(array_filter(self::TRANSITIONS, fn (array $rule) => in_array($article->status, $rule[0], true)));
    }

    public function storeFile(Article $article, UploadedFile $file, User $uploader, string $type = 'manuscript'): ArticleFile
    {
        return $article->files()->create([
            'uploaded_by' => $uploader->id,
            'type' => $type,
            'path' => $file->store("articles/{$article->id}", 'local'),
            'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 200),
            'size' => $file->getSize(),
        ]);
    }

    /** What is still missing before a draft can be sent to the editors. */
    public function missingForSubmission(Article $article): array
    {
        $missing = [];

        if (! $article->files()->exists()) {
            $missing[] = 'file';
        }
        if (blank($article->title)) {
            $missing[] = 'title';
        }
        if (blank($article->abstract)) {
            $missing[] = 'abstract';
        }
        if (! $article->authors()->exists()) {
            $missing[] = 'authors';
        }

        return $missing;
    }

    public function submit(Article $article): Article
    {
        if (! $article->isDraft()) {
            throw new WorkflowException(__('site.workflow.not_draft'));
        }

        if ($missing = $this->missingForSubmission($article->fresh())) {
            throw new WorkflowException(__('site.workflow.incomplete', ['items' => collect($missing)->map(fn ($m) => __('site.workflow.missing.'.$m))->join(', ')]));
        }

        $article->update(['status' => 'submitted', 'submitted_at' => now()]);
        $this->notifyEditors(new ArticleSubmitted($article));

        return $article;
    }

    public function resubmitRevision(Article $article, UploadedFile $file, User $author): Article
    {
        if ($article->status !== 'revisions') {
            throw new WorkflowException(__('site.workflow.no_revision_requested'));
        }

        DB::transaction(function () use ($article, $file, $author) {
            $this->storeFile($article, $file, $author, 'revision');
            $article->update(['status' => 'submitted', 'submitted_at' => now()]);
        });

        $this->notifyEditors(new ArticleSubmitted($article));

        return $article;
    }

    public function assignReviewer(Article $article, User $reviewer, User $editor, ?CarbonInterface $dueAt = null): Review
    {
        if (! in_array($article->status, ['submitted', 'in_review'], true)) {
            throw new WorkflowException(__('site.workflow.cannot_assign'));
        }
        if (! $reviewer->hasRole('reviewer')) {
            throw new WorkflowException(__('site.workflow.not_a_reviewer'));
        }
        if ($this->isAuthorOf($article, $reviewer)) {
            throw new WorkflowException(__('site.workflow.conflict_of_interest'));
        }
        if ($article->reviews()->where('reviewer_id', $reviewer->id)->exists()) {
            throw new WorkflowException(__('site.workflow.already_assigned'));
        }

        $review = DB::transaction(function () use ($article, $reviewer, $editor, $dueAt) {
            $review = $article->reviews()->create([
                'reviewer_id' => $reviewer->id,
                'assigned_by' => $editor->id,
                'status' => 'pending',
                'due_at' => $dueAt?->toDateString(),
            ]);
            $article->update(['status' => 'in_review']);

            return $review;
        });

        $reviewer->notify(new ReviewAssigned($article));

        return $review;
    }

    public function respondToInvitation(Review $review, bool $accept): Review
    {
        if ($review->status !== 'pending') {
            throw new WorkflowException(__('site.workflow.already_answered'));
        }

        $review->update(['status' => $accept ? 'accepted' : 'declined', 'responded_at' => now()]);

        if (! $accept) {
            $this->notifyEditors(new ReviewCompleted($review->article));
        }

        return $review;
    }

    /** @param array{recommendation: string, comments_to_author: string, comments_to_editor?: ?string} $data */
    public function completeReview(Review $review, array $data): Review
    {
        if ($review->status !== 'accepted') {
            throw new WorkflowException(__('site.workflow.review_not_open'));
        }
        if (! in_array($review->article->status, ['submitted', 'in_review'], true)) {
            throw new WorkflowException(__('site.workflow.review_closed'));
        }

        $review->update($data + ['status' => 'completed', 'completed_at' => now()]);
        $this->notifyEditors(new ReviewCompleted($review->article));

        return $review;
    }

    public function decide(Article $article, User $editor, string $decision, ?string $comment = null, ?Issue $issue = null): EditorialDecision
    {
        [$from, $to] = self::TRANSITIONS[$decision] ?? throw new WorkflowException(__('site.workflow.unknown_decision'));

        if (! in_array($article->status, $from, true)) {
            throw new WorkflowException(__('site.workflow.decision_not_allowed', ['status' => __('site.statuses.'.$article->status)]));
        }

        $issue ??= $article->issue;
        if ($decision === 'publish' && ! $issue) {
            throw new WorkflowException(__('site.workflow.issue_required'));
        }

        $record = DB::transaction(function () use ($article, $editor, $decision, $comment, $issue, $to) {
            $changes = ['status' => $to];
            if ($decision === 'publish') {
                $changes += ['issue_id' => $issue->id, 'published_at' => now()->toDateString()];
            }
            $article->update($changes);

            return $article->decisions()->create(['editor_id' => $editor->id, 'decision' => $decision, 'comment' => $comment]);
        });

        if ($article->submitter) {
            $article->submitter->notify(new DecisionMade($article, $decision));
        }

        return $record;
    }

    /** A reviewer must not be (or be listed as) an author of the manuscript. */
    public function isAuthorOf(Article $article, User $user): bool
    {
        return $article->submitter_id === $user->id
            || $article->authors()->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('email', $user->email))->exists();
    }

    private function notifyEditors(object $notification): void
    {
        $editors = User::query()
            ->where('is_admin', true)
            ->orWhereJsonContains('roles', 'editor_in_chief')
            ->orWhereJsonContains('roles', 'section_editor')
            ->get();

        Notification::send($editors, $notification);
    }
}
