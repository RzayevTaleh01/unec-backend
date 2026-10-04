<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Journal extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = ['name', 'slug', 'url', 'is_primary', 'sort_order'];

    protected $casts = ['is_primary' => 'boolean'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('roles');
    }
}
