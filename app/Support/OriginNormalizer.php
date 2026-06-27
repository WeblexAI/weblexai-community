<?php

namespace App\Support;

use InvalidArgumentException;

class OriginNormalizer
{
    public static function fromUrl(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $parts = parse_url(trim($value));

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $host = str_contains($parts['host'], ':') ? '['.$parts['host'].']' : $parts['host'];
        $origin = $parts['scheme'].'://'.$host.(isset($parts['port']) ? ':'.$parts['port'] : '');

        try {
            return (new self)->normalize($origin);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function normalize(string $value): string
    {
        $value = trim($value);
        $parts = parse_url($value);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException('Enter a complete HTTP or HTTPS origin.');
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Only HTTP and HTTPS origins are supported.');
        }

        foreach (['user', 'pass', 'path', 'query', 'fragment'] as $component) {
            if (isset($parts[$component]) && $parts[$component] !== '') {
                if ($component === 'path' && $parts[$component] === '/') {
                    continue;
                }

                throw new InvalidArgumentException('Origins cannot include credentials, paths, queries, or fragments.');
            }
        }

        $host = rtrim(strtolower($parts['host']), '.');
        if ($host === '' || str_contains($host, '*')) {
            throw new InvalidArgumentException('The origin host is required.');
        }

        if (! filter_var($host, FILTER_VALIDATE_IP) && function_exists('idn_to_ascii')) {
            $asciiHost = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($asciiHost === false) {
                throw new InvalidArgumentException('The origin host is invalid.');
            }
            $host = strtolower($asciiHost);
        }

        $port = $parts['port'] ?? null;
        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            $port = null;
        }

        $formattedHost = str_contains($host, ':') ? '['.$host.']' : $host;

        return $scheme.'://'.$formattedHost.($port ? ':'.$port : '');
    }
}
