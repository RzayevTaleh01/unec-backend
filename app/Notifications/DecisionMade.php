<?php

namespace App\Notifications;

use App\Models\Article;

/** To the submitting author when the editors decide on the article. */
class DecisionMade extends SubmissionNotification
{
    public function __construct(Article $article, private string $decision)
    {
        parent::__construct($article);
    }

    public static function type(): string
    {
        return 'decision_made';
    }

    protected function line(): string
    {
        return __('site.notify.decision_made', [
            'title' => $this->title(),
            'decision' => __('site.decisions.'.$this->decision),
        ]);
    }

    protected function url(): string
    {
        return route('profile.submissions.show', $this->article);
    }
}
