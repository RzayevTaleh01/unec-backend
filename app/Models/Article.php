<?php

namespace App\Models;

use App\Models\Concerns\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Article extends Model
{
    use HasTranslations, ResolvesMediaUrl;

    public const STATUSES = ['draft', 'submitted', 'in_review', 'revisions', 'accepted', 'rejected', 'published'];

    public array $translatable = ['title', 'abstract', 'keywords'];

    protected $fillable = [
        'issue_id', 'submitter_id', 'title', 'abstract', 'keywords', 'pdf', 'doi',
        'language', 'pages', 'status', 'submitted_at', 'published_at',
    ];

    protected $casts = ['published_at' => 'date', 'submitted_at' => 'datetime'];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_id');
    }

    public function authors(): HasMany
    {
        return $this->hasMany(ArticleAuthor::class)->orderBy('sort_order');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ArticleFile::class)->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(EditorialDecision::class)->latest('id');
    }

    /** The author may still change the submission itself (wizard steps). */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function getAuthorLineAttribute(): string
    {
        return $this->authors->pluck('name')->join(', ');
    }

    public function getPdfUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->pdf) ?? $this->issue?->pdf_url;
    }

    public function getDoiUrlAttribute(): ?string
    {
        $doi = $this->doi ?: $this->issue?->doi;

        return $doi ? (str_starts_with($doi, 'http') ? $doi : 'https://doi.org/'.$doi) : null;
    }

    /** Citation in APA, MLA or Chicago style. */
    public function cite(string $style = 'apa'): string
    {
        $year = $this->published_at?->year ?? $this->issue?->year;
        $journal = __('site.journal_name');
        $authors = $this->authors->pluck('name')->join(', ');
        $title = $this->title;
        $volume = $this->issue ? $this->issue->volume.'('.$this->issue->number.')' : '';
        $doi = $this->doi_url ? ' '.$this->doi_url : '';

        return match ($style) {
            'mla' => "{$authors}. \"{$title}.\" {$journal}, vol. {$this->issue?->volume}, {$year}.{$doi}",
            'chicago' => "{$authors}. {$year}. \"{$title}.\" {$journal} {$volume}.{$doi}",
            default => "{$authors} ({$year}). {$title}. {$journal}, {$volume}.{$doi}",
        };
    }
}
