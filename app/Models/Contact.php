<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Contact extends Model
{
    use HasTranslations;

    public array $translatable = ['title', 'organization'];

    protected $fillable = ['title', 'name', 'organization', 'phone', 'email', 'sort_order'];
}
