<?php

namespace App\Support;

class Html
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><ul><ol><li><a><h2><h3><h4><blockquote><table><thead><tbody><tr><th><td><img><span><div><code><pre><hr>';

    /**
     * Admin- and user-authored rich text is rendered unescaped, so reduce it to
     * basic formatting tags and drop event handlers and script-like URLs first.
     */
    public static function clean(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        // strip_tags keeps the text inside <script>/<style>, so remove those blocks first.
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);
        $html = strip_tags($html, self::ALLOWED_TAGS);

        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/\s(href|src)\s*=\s*("|\')\s*(javascript|data|vbscript):[^"\']*\2/i', '', $html);

        return $html;
    }
}
