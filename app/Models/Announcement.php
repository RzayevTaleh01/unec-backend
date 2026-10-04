<?php

namespace App\Models;

use App\Models\Concerns\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Announcement extends Model
{
    use HasTranslations, ResolvesMediaUrl;

    public array $translatable = ['title', 'body'];

    protected $fillable = ['slug', 'title', 'body', 'image', 'published_at', 'views_count', 'is_published'];

    protected $casts = ['published_at' => 'date', 'is_published' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Announcement $announcement) {
            if (blank($announcement->slug)) {
                $base = Str::slug($announcement->getTranslation('title', 'az', false) ?: 'elan');
                $announcement->slug = Str::limit($base, 60, '').'-'.Str::lower(Str::random(5));
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->whereDate('published_at', '<=', now());
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->image);
    }
}
