<?php

namespace App\Http\Controllers\Submission;

use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\SubmissionWorkflow;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private SubmissionWorkflow $workflow)
    {
    }

    public function index(Request $request)
    {
        $reviews = Review::where('reviewer_id', $request->user()->id)->with('article')->latest()->get();

        return view('profile.reviews', [
            'open' => $reviews->filter->isOpen(),
            'done' => $reviews->reject->isOpen(),
        ]);
    }

    public function show(Request $request, Review $review)
    {
        $this->own($request, $review);

        // Blind review: authors are intentionally not loaded for this view.
        $review->load('article.files');

        return view('profile.review-show', compact('review'));
    }

    public function respond(Request $request, Review $review)
    {
        $this->own($request, $review);

        $data = $request->validate(['answer' => ['required', 'in:accept,decline']]);

        try {
            $this->workflow->respondToInvitation($review, $data['answer'] === 'accept');
        } catch (WorkflowException $e) {
            return back()->withErrors(['answer' => $e->getMessage()]);
        }

        return redirect()->route('profile.reviews.show', $review)->with('status', __('site.reviews.answered'));
    }

    public function submit(Request $request, Review $review)
    {
        $this->own($request, $review);

        $data = $request->validate([
            'recommendation' => ['required', 'in:'.implode(',', Review::RECOMMENDATIONS)],
            'comments_to_author' => ['required', 'string', 'min:20', 'max:10000'],
            'comments_to_editor' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->workflow->completeReview($review, $data);
        } catch (WorkflowException $e) {
            return back()->withErrors(['recommendation' => $e->getMessage()]);
        }

        return redirect()->route('profile.reviews')->with('status', __('site.reviews.submitted'));
    }

    private function own(Request $request, Review $review): void
    {
        abort_unless($review->reviewer_id === $request->user()->id, 404);
    }
}
