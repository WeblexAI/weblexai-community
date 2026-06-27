<?php

use Opcodes\LogViewer\Http\Middleware\AuthorizeLogViewer;
use Opcodes\LogViewer\Http\Middleware\EnsureFrontendRequestsAreStateful;

return [
    'enabled' => env('LOG_VIEWER_ENABLED', true),
    'require_auth_in_production' => true,
    'route_path' => 'log-viewer',
    'back_to_system_url' => '/admin',
    'back_to_system_label' => 'Back to WeblexAI administration',
    'middleware' => [
        'web',
        AuthorizeLogViewer::class,
    ],
    'api_middleware' => [
        EnsureFrontendRequestsAreStateful::class,
        AuthorizeLogViewer::class,
    ],
    'include_files' => [
        '*.log',
        '**/*.log',
    ],
    'hide_unknown_files' => true,
    'cache_driver' => env('LOG_VIEWER_CACHE_DRIVER', env('CACHE_STORE', 'file')),
];
