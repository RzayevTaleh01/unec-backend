<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    public const RECOMMENDATIONS = ['accept', 'minor_revisions', 'major_revisions', 'reject'];

    protected $fillable = [
        'article_id', 'reviewer_id', 'assigned_by', 'status', 'recommendation',
        'comments_to_author', 'comments_to_editor', 'due_at', 'responded_at', 'completed_at',
    ];

    protected $casts = ['due_at' => 'date', 'responded_at' => 'datetime', 'completed_at' => 'datetime'];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /** The reviewer can still act on it (answer the invitation or write the review). */
    public function isOpen(): bool
    {
        return in_array($this->status, ['pending', 'accepted'], true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_at?->isPast();
    }
}
