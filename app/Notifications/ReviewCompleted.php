<?php

namespace App\Notifications;

/** To editors/admins when a reviewer submits a review (or declines the invitation). */
class ReviewCompleted extends SubmissionNotification
{
    public static function type(): string
    {
        return 'reviewer_commented';
    }

    protected function line(): string
    {
        return __('site.notify.review_completed', ['title' => $this->title()]);
    }

    protected function url(): string
    {
        return url('/admin/articles/'.$this->article->id.'/edit');
    }
}
