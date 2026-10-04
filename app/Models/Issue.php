<?php

namespace App\Models;

use App\Models\Concerns\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Issue extends Model
{
    use HasTranslations, ResolvesMediaUrl;

    public array $translatable = ['title', 'description'];

    protected $fillable = [
        'volume', 'number', 'year', 'title', 'description', 'cover', 'pdf', 'doi', 'published_at', 'is_published',
    ];

    protected $casts = ['published_at' => 'date', 'is_published' => 'boolean'];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class)->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('year')->orderByDesc('volume')->orderByDesc('number');
    }

    /** Issue label in the active locale, e.g. "Cild. 3 Nömrə. 1 (2026)". */
    public function getLabelAttribute(): string
    {
        return __('site.issue_label', ['volume' => $this->volume, 'number' => $this->number, 'year' => $this->year]);
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->cover) ?? asset('assets/images/archive/archive-1.png');
    }

    public function getPdfUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->pdf);
    }

    public function getDoiUrlAttribute(): ?string
    {
        return $this->doi ? (str_starts_with($this->doi, 'http') ? $this->doi : 'https://doi.org/'.$this->doi) : null;
    }
}
