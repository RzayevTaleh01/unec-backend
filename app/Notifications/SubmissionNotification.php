<?php

namespace App\Notifications;

use App\Models\Article;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for workflow notifications. Delivery respects the per-type choices a user
 * saved on /profile/notifications (in-app and email can be switched off separately).
 */
abstract class SubmissionNotification extends Notification
{
    /** Key in NotificationSetting::GROUPS and lang site.notification_types. */
    abstract public static function type(): string;

    abstract protected function line(): string;

    abstract protected function url(): string;

    public function __construct(protected Article $article)
    {
    }

    public function via(object $notifiable): array
    {
        $setting = $notifiable->notificationSettings()->where('type', static::type())->first();

        return array_values(array_filter([
            ($setting?->in_app ?? true) ? 'database' : null,
            ($setting?->email ?? true) ? 'mail' : null,
        ]));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('site.journal_name').': '.__('site.notification_types.'.static::type()))
            ->line($this->line())
            ->action(__('site.notify.open'), $this->url());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => static::type(),
            'article_id' => $this->article->id,
            'message' => $this->line(),
            'url' => $this->url(),
        ];
    }

    protected function title(): string
    {
        return $this->article->title ?: __('site.wizard.untitled');
    }
}
