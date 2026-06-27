<?php

return [
    'installed' => env('APP_INSTALLED', false),
    'version' => env('APP_VERSION', '1.0.0'),
    'deployment_mode' => env('WEBLEX_DEPLOYMENT_MODE', 'traditional'),
    'github_url' => env('WEBLEX_GITHUB_URL') ?: 'https://github.com/weblexai/weblexai-community',
    'docs_url' => env('WEBLEX_DOCS_URL') ?: '#',
    'release_feed_url' => env('RELEASE_FEED_URL'),
    'release_public_key' => env('RELEASE_PUBLIC_KEY'),
    'update_check_hours' => (int) env('UPDATE_CHECK_HOURS', 24),
    'update_driver' => env('UPDATE_DRIVER', 'disabled'),
    'update_base_path' => env('UPDATE_BASE_PATH', '/srv/weblex'),
    'update_backup_path' => env('UPDATE_BACKUP_PATH', '/var/backups/weblex'),
    'update_agent_url' => env('UPDATE_AGENT_URL'),
    'update_agent_secret' => env('UPDATE_AGENT_SECRET'),
];
