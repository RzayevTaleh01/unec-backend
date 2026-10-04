<?php

namespace App\Notifications;

/** To editors/admins when an author completes a submission. */
class ArticleSubmitted extends SubmissionNotification
{
    public static function type(): string
    {
        return 'article_submitted';
    }

    protected function line(): string
    {
        return __('site.notify.article_submitted', ['title' => $this->title()]);
    }

    protected function url(): string
    {
        return url('/admin/articles/'.$this->article->id.'/edit');
    }
}
