<?php

return [
    'paths' => ['api/project/*', 'wlai/*'],
    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => [
        'Authorization',
        'Content-Type',
        'Origin',
        'X-Page-Url',
        'X-Page-Title',
    ],
    'exposed_headers' => [],
    'max_age' => 600,
    'supports_credentials' => false,
];
