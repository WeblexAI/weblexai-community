<?php

use Spatie\Health\Models\HealthCheckResultHistoryItem;
use Spatie\Health\Notifications\CheckFailedNotification;
use Spatie\Health\Notifications\Notifiable;
use Spatie\Health\ResultStores\EloquentHealthResultStore;

return [
    'result_stores' => [
        EloquentHealthResultStore::class => [
            'connection' => env('HEALTH_DB_CONNECTION', env('DB_CONNECTION')),
            'model' => HealthCheckResultHistoryItem::class,
            'keep_history_for_days' => env('HEALTH_HISTORY_DAYS', 7),
        ],
    ],

    'notifications' => [
        'enabled' => env('HEALTH_NOTIFICATIONS_ENABLED', false),

        'notifications' => [
            CheckFailedNotification::class => ['mail'],
        ],

        'notifiable' => Notifiable::class,
        'throttle_notifications_for_minutes' => 60,
        'throttle_notifications_key' => 'health:latestNotificationSentAt:',
        'only_on_failure' => true,

        'mail' => [
            'to' => env('HEALTH_TO_ADDRESS', ''),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'WeblexAI')),
            ],
        ],

        'slack' => [
            'webhook_url' => env('HEALTH_SLACK_WEBHOOK_URL', ''),
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],
    ],

    'disk' => [
        'warn_when_used_space_is_above_percentage' => env('HEALTH_DISK_WARN_THRESHOLD', 80),
        'fail_when_used_space_is_above_percentage' => env('HEALTH_DISK_FAIL_THRESHOLD', 90),
    ],

    'oh_dear_endpoint' => [
        'enabled' => false,
        'always_send_fresh_results' => true,
        'secret' => env('OH_DEAR_HEALTH_CHECK_SECRET'),
        'url' => '/oh-dear-health-check-results',
    ],

    'horizon' => [
        'heartbeat_url' => env('HORIZON_HEARTBEAT_URL'),
    ],

    'schedule' => [
        'heartbeat_url' => env('SCHEDULE_HEARTBEAT_URL'),
    ],

    'theme' => 'light',
    'silence_health_queue_job' => true,
    'json_results_failure_status' => 200,
    'secret_token' => env('HEALTH_SECRET_TOKEN'),
];
