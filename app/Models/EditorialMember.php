<?php

namespace App\Models;

use App\Models\Concerns\ResolvesMediaUrl;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class EditorialMember extends Model
{
    use HasTranslations, ResolvesMediaUrl;

    public array $translatable = ['role'];

    protected $fillable = ['name', 'role', 'photo', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->photo);
    }
}
