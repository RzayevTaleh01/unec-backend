<?php

namespace App\Notifications;

/** To a reviewer who was invited to review a manuscript. */
class ReviewAssigned extends SubmissionNotification
{
    public static function type(): string
    {
        return 'review_assigned';
    }

    protected function line(): string
    {
        return __('site.notify.review_assigned', ['title' => $this->title()]);
    }

    protected function url(): string
    {
        return route('profile.reviews');
    }
}
