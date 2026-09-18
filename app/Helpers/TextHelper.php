<?php

if (! function_exists('initials')) {
    /**
     * Initials for an avatar badge.
     *
     * Taking the first character of each word turns "Shorif (Global Furniture)"
     * into "S(" — punctuation is not an initial. Brackets, quotes, dots and the
     * like are dropped first, so that name reads "SG".
     *
     * Unicode-aware on purpose: names here are often Bengali, and \p{L} keeps
     * those letters while still discarding punctuation.
     */
    function initials(?string $name, int $max = 2, string $fallback = '?'): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return $fallback;
        }

        // Punctuation becomes a separator rather than being deleted, so
        // "Rahim-Karim" still yields two initials instead of one run-on word.
        // Combining marks are kept: Bengali vowel signs are marks, not
        // letters, and stripping them tears জাহিদ into three "words".
        $words = preg_split(
            '/\s+/u',
            trim(preg_replace('/[^\p{L}\p{N}\p{M}]+/u', ' ', $name)),
            -1,
            PREG_SPLIT_NO_EMPTY,
        );

        if (empty($words)) {
            return $fallback;
        }

        $out = '';

        foreach (array_slice($words, 0, max(1, $max)) as $word) {
            // \X takes a whole grapheme, so a consonant keeps the vowel sign
            // attached to it rather than being shown bare.
            $out .= preg_match('/\X/u', $word, $m) ? mb_strtoupper($m[0]) : '';
        }

        return $out !== '' ? $out : $fallback;
    }
}
