<?php

namespace App\Support;

use InvalidArgumentException;

class UrlHelper
{
    public function getDomainAndOrigin(string $url): array
    {
        $url = urldecode(trim($url));
        $origin = OriginNormalizer::fromUrl($url);
        $parts = parse_url($url);

        if (! $origin || $parts === false || ! isset($parts['host'])) {
            throw new InvalidArgumentException('The page URL is invalid.');
        }

        $host = strtolower($parts['host']);
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = '/'.ltrim($parts['path'] ?? '', '/');
        $path = $path === '/' ? '/' : rtrim($path, '/');
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return [$origin.$path.$query, $host.$port.$path.$query];
    }
}
