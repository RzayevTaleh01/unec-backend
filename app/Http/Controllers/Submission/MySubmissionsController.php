<?php

namespace App\Http\Controllers\Submission;

use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\SubmissionWorkflow;
use Illuminate\Http\Request;

class MySubmissionsController extends Controller
{
    public function __construct(private SubmissionWorkflow $workflow)
    {
    }

    public function index(Request $request)
    {
        return view('profile.submissions', [
            'articles' => $request->user()->submissions()->with('authors')->latest()->get(),
        ]);
    }

    public function show(Request $request, Article $article)
    {
        abort_unless($article->submitter_id === $request->user()->id, 404);

        $article->load('authors', 'files', 'decisions.editor');

        // Reviewers stay anonymous. Authors only see the comments meant for them,
        // and only once the editors have made a decision.
        $feedback = $article->decisions->isEmpty()
            ? collect()
            : $article->reviews()->where('status', 'completed')->whereNotNull('comments_to_author')->get();

        return view('profile.submission-show', compact('article', 'feedback'));
    }

    public function storeRevision(Request $request, Article $article)
    {
        abort_unless($article->submitter_id === $request->user()->id, 404);

        $request->validate(['manuscript' => SubmissionWizardController::FILE_RULES]);

        try {
            $this->workflow->resubmitRevision($article, $request->file('manuscript'), $request->user());
        } catch (WorkflowException $e) {
            return back()->withErrors(['manuscript' => $e->getMessage()]);
        }

        return back()->with('status', __('site.wizard.revision_sent'));
    }
}
