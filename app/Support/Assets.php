<?php

namespace App\Support;

class Assets
{
    /**
     * URL of a file under public/ with a cache-busting version (its modification time),
     * so browsers fetch new CSS/JS right after a change instead of reusing an old copy.
     */
    public static function url(string $path): string
    {
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}
