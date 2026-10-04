<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'type', 'in_app', 'email'];

    protected $casts = ['in_app' => 'boolean', 'email' => 'boolean'];

    /** Notification types shown on profile/notifications, grouped as in the design. */
    public const GROUPS = [
        'general' => ['announcement_created', 'issue_published', 'issue_open_access'],
        'article' => ['article_submitted', 'decision_made', 'editor_assignment_needed', 'discussion_added', 'discussion_activity'],
        'review' => ['review_assigned', 'reviewer_commented'],
        'editors' => ['weekly_digest', 'stats_report'],
    ];
}
