<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleAuthor extends Model
{
    public $timestamps = false;

    protected $fillable = ['article_id', 'user_id', 'name', 'institution', 'email', 'is_primary', 'sort_order'];

    protected $casts = ['is_primary' => 'boolean'];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
