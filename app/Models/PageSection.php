<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class PageSection extends Model
{
    use HasTranslations;

    public array $translatable = ['title'];

    protected $fillable = ['key', 'title', 'sort_order'];

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class)->orderBy('sort_order');
    }
}
