<?php

use App\Support\OriginNormalizer;

it('normalizes exact HTTP origins', function (string $input, string $expected) {
    expect((new OriginNormalizer)->normalize($input))->toBe($expected);
})->with([
    ['HTTPS://Example.COM/', 'https://example.com'],
    ['http://localhost:3000', 'http://localhost:3000'],
    ['https://example.com:443', 'https://example.com'],
    ['http://127.0.0.1:80', 'http://127.0.0.1'],
]);

it('rejects origins containing non-origin components', function (string $input) {
    (new OriginNormalizer)->normalize($input);
})->with([
    'path' => ['https://example.com/path'],
    'query' => ['https://example.com?test=1'],
    'fragment' => ['https://example.com#test'],
    'wildcard' => ['https://*.example.com'],
])->throws(InvalidArgumentException::class);
