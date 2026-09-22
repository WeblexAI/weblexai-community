<?php

return [
    'installed' => env('APP_INSTALLED', false),
    'version' => env('APP_VERSION', 'latest'),
    'github_url' => env('WEBLEX_GITHUB_URL') ?: 'https://github.com/weblexai/weblexai-community',
    'docs_url' => env('WEBLEX_DOCS_URL') ?: 'https://github.com/weblexai/weblexai-community/tree/main/docs',
];
