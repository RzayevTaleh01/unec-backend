<?php

namespace Tests\Feature;

use App\Exceptions\WorkflowException;
use App\Filament\Resources\ArticleResource\Pages\EditArticle;
use App\Filament\Resources\ArticleResource\RelationManagers\DecisionsRelationManager;
use App\Filament\Resources\ArticleResource\RelationManagers\FilesRelationManager;
use App\Filament\Resources\ArticleResource\RelationManagers\ReviewsRelationManager;
use App\Models\Article;
use App\Models\Issue;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ArticleSubmitted;
use App\Notifications\DecisionMade;
use App\Notifications\ReviewAssigned;
use App\Services\SubmissionWorkflow;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SubmissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $admin;

    private User $reviewer;

    private SubmissionWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(ContentSeeder::class);

        $this->author = $this->makeUser('author', ['author']);
        $this->reviewer = $this->makeUser('reviewer', ['reader', 'reviewer']);
        $this->admin = $this->makeUser('editor', ['reader']);
        $this->admin->forceFill(['is_admin' => true])->save();

        $this->workflow = app(SubmissionWorkflow::class);
    }

    private function makeUser(string $name, array $roles): User
    {
        return User::factory()->create(['first_name' => ucfirst($name), 'last_name' => 'Tester', 'username' => $name, 'roles' => $roles]);
    }

    private function doc(string $name = 'paper.docx'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 120, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    private function wizardPayload(array $overrides = []): array
    {
        return array_merge([
            'mode' => 'submit',
            'language' => 'en',
            'checklist' => \App\Http\Controllers\Submission\SubmissionWizardController::CHECKLIST,
            'copyright' => '1',
            'manuscript' => $this->doc(),
            'title' => 'Fintech and SMEs',
            'abstract' => str_repeat('A long enough abstract. ', 5),
            'keywords' => 'fintech, sme',
            'authors' => [['name' => 'Author Tester', 'email' => 'a@example.com'], ['name' => 'Co Author'], ['name' => '']],
            'primary' => 0,
        ], $overrides);
    }

    /** Posts the single-page wizard once and returns the sent article. */
    private function submitThroughWizard(?User $as = null): Article
    {
        $this->actingAs($as ?? $this->author);

        $this->post('/submit', $this->wizardPayload());
        $article = Article::latest('id')->first();
        $this->assertSame('submitted', $article->status);

        return $article->fresh();
    }

    private function newDraft(array $overrides = []): Article
    {
        $this->actingAs($this->author);
        $this->post('/submit', ['mode' => 'draft', 'language' => 'az'] + $overrides)->assertSessionHasNoErrors();

        return Article::latest('id')->first();
    }

    public function test_the_wizard_is_one_page_with_five_js_steps(): void
    {
        $this->actingAs($this->author);
        $html = $this->get('/submit')->assertOk()->getContent();

        foreach ([1, 2, 3, 4, 5] as $step) {
            $this->assertStringContainsString("data-wizard-step=\"{$step}\"", $html);
            $this->assertStringContainsString("data-wizard-goto=\"{$step}\"", $html);
        }
        $this->assertStringContainsString('assets/js/wizard.js', $html);
        $this->assertStringContainsString('novalidate', $html);
    }

    public function test_old_step_urls_redirect_to_the_single_page(): void
    {
        $article = $this->newDraft();

        foreach (['upload', 'metadata', 'contributors', 'review'] as $step) {
            $this->get("/submit/{$article->id}/{$step}")->assertRedirect("/submit/{$article->id}");
        }
        $this->get("/submit/{$article->id}")->assertOk();
    }

    public function test_full_wizard_submits_article_and_notifies_editors(): void
    {
        $article = $this->submitThroughWizard();

        $this->assertSame('submitted', $article->status);
        $this->assertNotNull($article->submitted_at);
        $this->assertSame($this->author->id, $article->submitter_id);
        $this->assertSame('Fintech and SMEs', $article->getTranslation('title', 'en'));
        $this->assertSame(['Author Tester', 'Co Author'], $article->authors->pluck('name')->all());
        $this->assertTrue($article->authors->first()->is_primary);
        Storage::disk('local')->assertExists($article->files->first()->path);

        $this->assertSame(1, $this->admin->notifications()->count());
        $this->assertSame('article_submitted', $this->admin->notifications()->first()->data['type']);
        $this->assertSame(0, $this->author->notifications()->count());

        // Drafts are the only editable state, and a sent article is not public.
        $this->get("/submit/{$article->id}")->assertForbidden();
        $this->get("/articles/{$article->id}")->assertNotFound();
    }

    public function test_the_final_send_redirects_to_the_submission_page(): void
    {
        $this->actingAs($this->author);

        $this->post('/submit', $this->wizardPayload());
        $article = Article::latest('id')->first();

        $this->assertTrue(session()->has('status'));
        $this->get("/profile/submissions/{$article->id}")->assertOk()->assertSee('Fintech and SMEs');
    }

    public function test_checklist_and_copyright_must_be_fully_confirmed(): void
    {
        $this->actingAs($this->author);
        $before = Article::count();

        $this->post('/submit', $this->wizardPayload(['checklist' => ['guidelines', 'original']]))->assertSessionHasErrors('checklist');
        $this->post('/submit', $this->wizardPayload(['checklist' => []]))->assertSessionHasErrors('checklist');
        $this->post('/submit', $this->wizardPayload(['copyright' => null]))->assertSessionHasErrors('copyright');

        $this->assertSame($before, Article::count());
    }

    public function test_upload_only_accepts_document_files(): void
    {
        $this->actingAs($this->author);

        $this->post('/submit', $this->wizardPayload(['manuscript' => UploadedFile::fake()->create('evil.php', 5, 'text/x-php')]))->assertSessionHasErrors('manuscript');
        $this->post('/submit', $this->wizardPayload(['manuscript' => UploadedFile::fake()->create('big.pdf', 30000, 'application/pdf')]))->assertSessionHasErrors('manuscript');
        $this->post('/submit', $this->wizardPayload(['manuscript' => null]))->assertSessionHasErrors('manuscript');
    }

    public function test_a_new_upload_replaces_the_draft_manuscript(): void
    {
        $article = $this->newDraft(['manuscript' => $this->doc('one.docx')]);
        $this->put("/submit/{$article->id}", ['mode' => 'draft', 'language' => 'az', 'manuscript' => $this->doc('two.docx')])->assertSessionHasNoErrors();

        $this->assertSame(['two.docx'], $article->files()->pluck('original_name')->all());
        $this->assertCount(1, Storage::disk('local')->files("articles/{$article->id}"));
    }

    public function test_drafts_save_incomplete_data_and_can_be_resumed_then_sent(): void
    {
        $article = $this->newDraft(['title' => 'Half written', 'keywords' => 'a, b']);

        $this->assertSame('draft', $article->status);
        $this->assertSame('Half written', $article->getTranslation('title', 'az', false));

        // Resuming shows what was saved.
        $this->get("/submit/{$article->id}")->assertOk()->assertSee('Half written');

        // Sending an incomplete draft is refused and the draft is kept.
        $this->put("/submit/{$article->id}", $this->wizardPayload(['language' => 'az', 'manuscript' => null, 'abstract' => null]))
            ->assertSessionHasErrors(['manuscript', 'abstract']);
        $this->assertSame('draft', $article->fresh()->status);

        // Completing and sending it works.
        $this->put("/submit/{$article->id}", $this->wizardPayload(['language' => 'az']))->assertSessionHasNoErrors();
        $this->assertSame('submitted', $article->fresh()->status);
        $this->assertSame(['Author Tester', 'Co Author'], $article->authors()->pluck('name')->all());
    }

    public function test_incomplete_final_submissions_are_rejected_without_creating_anything(): void
    {
        $this->actingAs($this->author);
        $before = Article::count();

        $this->post('/submit', $this->wizardPayload(['title' => null, 'abstract' => 'too short', 'keywords' => null, 'authors' => null]))
            ->assertSessionHasErrors(['title', 'abstract', 'keywords', 'authors']);
        $this->post('/submit', $this->wizardPayload(['authors' => [['name' => 'X', 'email' => 'not-an-email']]]))
            ->assertSessionHasErrors('authors.0.email');

        $this->assertSame($before, Article::count());
    }

    public function test_other_users_cannot_touch_someone_elses_draft_or_submission(): void
    {
        $draft = $this->newDraft();
        $sent = $this->submitThroughWizard();
        $stranger = $this->makeUser('stranger', ['author']);
        $this->actingAs($stranger);

        $this->get("/profile/submissions/{$sent->id}")->assertNotFound();
        $this->get("/submit/{$draft->id}")->assertNotFound();
        $this->put("/submit/{$draft->id}", $this->wizardPayload())->assertNotFound();
        $this->delete("/submit/{$draft->id}")->assertNotFound();
        $this->get('/files/'.$sent->files->first()->id)->assertNotFound();
    }

    public function test_draft_can_be_deleted_with_its_files(): void
    {
        $article = $this->newDraft(['manuscript' => $this->doc()]);

        $this->delete("/submit/{$article->id}")->assertRedirect('/profile/submissions');

        $this->assertNull(Article::find($article->id));
        $this->assertSame([], Storage::disk('local')->files("articles/{$article->id}"));
    }

    public function test_reviewer_assignment_rules(): void
    {
        $article = $this->submitThroughWizard();

        // Authors cannot review their own work, and only reviewers can be assigned.
        $this->assertThrows(fn () => $this->workflow->assignReviewer($article, $this->author, $this->admin), WorkflowException::class);
        $this->assertThrows(fn () => $this->workflow->assignReviewer($article, $this->makeUser('plain', ['reader']), $this->admin), WorkflowException::class);

        Notification::fake();
        $review = $this->workflow->assignReviewer($article, $this->reviewer, $this->admin, now()->addDays(14));
        Notification::assertSentTo($this->reviewer, ReviewAssigned::class);

        $this->assertSame('in_review', $article->fresh()->status);
        $this->assertSame('pending', $review->status);
        $this->assertThrows(fn () => $this->workflow->assignReviewer($article->fresh(), $this->reviewer, $this->admin), WorkflowException::class);
    }

    public function test_co_author_by_email_cannot_be_reviewer(): void
    {
        $article = $this->submitThroughWizard();
        $article->authors()->create(['name' => 'Rev Tester', 'email' => $this->reviewer->email, 'sort_order' => 9]);

        $this->assertThrows(fn () => $this->workflow->assignReviewer($article, $this->reviewer, $this->admin), WorkflowException::class);
    }

    public function test_reviewer_flow_is_blind_and_access_controlled(): void
    {
        $article = $this->submitThroughWizard();
        $review = $this->workflow->assignReviewer($article, $this->reviewer, $this->admin);
        $file = $article->files->first();

        // Pending: reviewer sees the abstract but cannot download the manuscript yet.
        $this->actingAs($this->reviewer);
        $this->get('/profile/reviews')->assertOk()->assertSee('Fintech and SMEs');
        $this->get("/profile/reviews/{$review->id}")->assertOk()->assertSee('A long enough abstract')
            ->assertDontSee('Author Tester')->assertDontSee('Co Author');
        $this->get("/files/{$file->id}")->assertNotFound();
        $this->post("/profile/reviews/{$review->id}/submit", ['recommendation' => 'accept', 'comments_to_author' => str_repeat('x', 30)])
            ->assertSessionHasErrors('recommendation');

        // Accepting unlocks the file, under a neutral name.
        $this->post("/profile/reviews/{$review->id}/respond", ['answer' => 'accept'])->assertRedirect();
        $download = $this->get("/files/{$file->id}")->assertOk();
        $this->assertStringContainsString('manuscript-'.$file->id.'.docx', $download->headers->get('content-disposition'));
        $this->assertStringNotContainsString('paper', $download->headers->get('content-disposition'));
        $this->get("/profile/reviews/{$review->id}")->assertDontSee('Author Tester');

        // Submitting needs substantive comments.
        $this->post("/profile/reviews/{$review->id}/submit", ['recommendation' => 'accept', 'comments_to_author' => 'short'])
            ->assertSessionHasErrors('comments_to_author');
        $this->post("/profile/reviews/{$review->id}/submit", ['recommendation' => 'major_revisions', 'comments_to_author' => 'Please add robustness checks to the model.', 'comments_to_editor' => 'Weak methodology.'])
            ->assertRedirect('/profile/reviews');

        $this->assertSame('completed', $review->fresh()->status);
        $this->assertTrue($this->admin->notifications()->where('data->type', 'reviewer_commented')->exists());

        // Another reviewer / the author cannot open this review.
        $this->actingAs($this->makeUser('other', ['reviewer']))->get("/profile/reviews/{$review->id}")->assertNotFound();
        $this->actingAs($this->author)->get("/profile/reviews/{$review->id}")->assertForbidden();
    }

    public function test_declining_closes_the_invitation_and_blocks_the_file(): void
    {
        $article = $this->submitThroughWizard();
        $review = $this->workflow->assignReviewer($article, $this->reviewer, $this->admin);

        $this->actingAs($this->reviewer)->post("/profile/reviews/{$review->id}/respond", ['answer' => 'decline']);

        $this->assertSame('declined', $review->fresh()->status);
        $this->get('/files/'.$article->files->first()->id)->assertNotFound();
        $this->post("/profile/reviews/{$review->id}/respond", ['answer' => 'accept'])->assertSessionHasErrors('answer');
    }

    public function test_decisions_follow_the_state_machine_and_publish_into_an_issue(): void
    {
        $article = $this->submitThroughWizard();
        $issue = Issue::latestFirst()->first();

        $this->assertThrows(fn () => $this->workflow->decide($article, $this->admin, 'publish', null, $issue), WorkflowException::class);

        $this->workflow->decide($article, $this->admin, 'accept');
        $this->assertSame('accepted', $article->fresh()->status);

        $this->assertThrows(fn () => $this->workflow->decide($article->fresh(), $this->admin, 'publish'), WorkflowException::class);
        $this->assertThrows(fn () => $this->workflow->decide($article->fresh(), $this->admin, 'revisions'), WorkflowException::class);
        $this->assertThrows(fn () => $this->workflow->decide($article->fresh(), $this->admin, 'nonsense'), WorkflowException::class);

        $this->workflow->decide($article->fresh(), $this->admin, 'publish', null, $issue);
        $article->refresh();

        $this->assertSame('published', $article->status);
        $this->assertSame($issue->id, $article->issue_id);
        $this->assertSame(now()->toDateString(), $article->published_at->toDateString());

        // Now public and searchable.
        auth()->logout();
        $this->get("/articles/{$article->id}")->assertOk();
        $this->get('/search?title=Fintech')->assertSee('Fintech and SMEs');
        $this->assertSame(['accept', 'publish'], $article->decisions()->reorder('id')->pluck('decision')->all());
    }

    public function test_revision_loop_and_author_feedback_visibility(): void
    {
        $article = $this->submitThroughWizard();
        $review = $this->workflow->assignReviewer($article, $this->reviewer, $this->admin);
        $this->workflow->respondToInvitation($review, true);
        $this->workflow->completeReview($review, [
            'recommendation' => 'minor_revisions',
            'comments_to_author' => 'Clarify the sampling approach.',
            'comments_to_editor' => 'SECRET-FOR-EDITOR',
        ]);

        // Before a decision, the author sees no reviewer text.
        $this->actingAs($this->author)->get("/profile/submissions/{$article->id}")->assertOk()->assertDontSee('Clarify the sampling approach');

        $this->workflow->decide($article->fresh(), $this->admin, 'revisions', 'Please revise and resubmit.');
        $this->assertSame('revisions', $article->fresh()->status);
        $this->assertSame(1, $this->author->notifications()->where('data->type', 'decision_made')->count());

        $this->get("/profile/submissions/{$article->id}")->assertOk()
            ->assertSee('Clarify the sampling approach')->assertSee('Please revise and resubmit.')
            ->assertDontSee('SECRET-FOR-EDITOR')->assertDontSee($this->reviewer->name);

        // Reviewers cannot add reviews once the article is no longer under review.
        $second = $this->workflow->assignReviewer($article->fresh()->forceFill(['status' => 'in_review']), $this->makeUser('rev2', ['reviewer']), $this->admin);
        $article->update(['status' => 'revisions']);
        $this->workflow->respondToInvitation($second, true);
        $this->assertThrows(fn () => $this->workflow->completeReview($second->fresh(), ['recommendation' => 'accept', 'comments_to_author' => str_repeat('y', 30)]), WorkflowException::class);

        // The author uploads a revision, which sends the article back to the editors.
        $this->post("/profile/submissions/{$article->id}/revision", ['manuscript' => $this->doc('revised.docx')])->assertSessionHasNoErrors();
        $article->refresh();
        $this->assertSame('submitted', $article->status);
        $this->assertSame(['manuscript', 'revision'], $article->files->pluck('type')->all());

        // Uploading when no revision was requested is refused.
        $this->post("/profile/submissions/{$article->id}/revision", ['manuscript' => $this->doc('again.docx')])->assertSessionHasErrors('manuscript');
    }

    public function test_notification_preferences_control_delivery_channels(): void
    {
        $this->reviewer->notificationSettings()->create(['type' => 'review_assigned', 'in_app' => false, 'email' => false]);
        $this->admin->notificationSettings()->create(['type' => 'article_submitted', 'in_app' => true, 'email' => false]);

        $this->assertSame([], (new ReviewAssigned(Article::first()))->via($this->reviewer));
        $this->assertSame(['database'], (new ArticleSubmitted(Article::first()))->via($this->admin));
        $this->assertSame(['database', 'mail'], (new DecisionMade(Article::first(), 'accept'))->via($this->author));
    }

    public function test_inbox_lists_and_marks_notifications_read(): void
    {
        $this->submitThroughWizard();
        $this->actingAs($this->admin);

        $this->assertSame(1, $this->admin->unreadNotifications()->count());
        $this->get('/profile/inbox')->assertOk()->assertSee('Fintech and SMEs');

        $this->post('/profile/inbox/read-all')->assertRedirect();
        $this->assertSame(0, $this->admin->fresh()->unreadNotifications()->count());
    }

    public function test_editor_assigns_reviewer_and_decides_from_the_admin_panel(): void
    {
        $article = $this->submitThroughWizard();
        $this->actingAs($this->admin);

        Livewire::test(EditArticle::class, ['record' => $article->id])
            ->callAction('assignReviewer', ['reviewer_id' => $this->reviewer->id, 'due_at' => now()->addWeek()->toDateString()])
            ->assertHasNoActionErrors();

        $this->assertSame('in_review', $article->fresh()->status);
        $this->assertSame(1, Review::where('article_id', $article->id)->count());

        Livewire::test(EditArticle::class, ['record' => $article->id])
            ->callAction('decide', ['decision' => 'accept', 'comment' => 'Well done'])
            ->assertHasNoActionErrors();

        $this->assertSame('accepted', $article->fresh()->status);

        $this->get('/admin/articles/'.$article->id.'/edit')->assertOk();

        // Relation managers load lazily, so render them directly.
        $manager = ['ownerRecord' => $article->fresh(), 'pageClass' => EditArticle::class];
        Livewire::test(DecisionsRelationManager::class, $manager)->assertSee('Well done');
        Livewire::test(ReviewsRelationManager::class, $manager)->assertSee($this->reviewer->name);
        Livewire::test(FilesRelationManager::class, $manager)->assertSee('paper.docx');
        $this->get('/files/'.$article->files->first()->id)->assertOk();
    }

    public function test_status_cannot_be_edited_directly_in_the_admin_form(): void
    {
        $article = $this->submitThroughWizard();
        $this->actingAs($this->admin);

        Livewire::test(EditArticle::class, ['record' => $article->id])
            ->fillForm(['status' => 'published'])
            ->call('save');

        $this->assertSame('submitted', $article->fresh()->status);
    }
}
