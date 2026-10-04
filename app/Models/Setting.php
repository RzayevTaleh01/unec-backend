<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Setting extends Model
{
    use HasTranslations;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public array $translatable = ['value'];

    protected $fillable = ['key', 'value'];

    /** Translated setting value with a plain default. */
    public static function get(string $key, ?string $default = null): ?string
    {
        return static::find($key)?->value ?: $default;
    }
}
