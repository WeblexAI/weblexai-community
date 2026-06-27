<?php

namespace App\Support;

class TextHasher
{
    public static function normalize(string $text): string
    {
        $normalized = trim($text);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized;
    }

    public static function hash(string $text): string
    {
        return hash('xxh128', self::normalize($text));
    }

    public static function hashMany(array $texts): array
    {
        return array_map(fn ($text) => self::hash($text), $texts);
    }
}
