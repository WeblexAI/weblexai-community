<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;

$backupName = env('APP_NAME', 'weblexai-community');
$backupDisk = 'backups';
$backupPath = env('BACKUP_PATH') ?: storage_path('app/backups');

return [
    'backup' => [
        'name' => $backupName,

        'source' => [
            'files' => [
                'include' => [
                    storage_path('app/public'),
                    storage_path('app/private'),
                    base_path('.env'),
                ],

                'exclude' => [
                    $backupPath,
                    storage_path('app/private/'.$backupName),
                    storage_path('app/backup-temp'),
                    storage_path('framework'),
                    storage_path('logs'),
                ],

                'follow_links' => false,
                'ignore_unreadable_directories' => true,
                'relative_path' => base_path(),
            ],

            'databases' => [
                env('DB_CONNECTION', 'pgsql'),
            ],
        ],

        'database_dump_compressor' => null,
        'database_dump_file_timestamp_format' => 'Y-m-d-H-i-s',
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 6,
            'filename_prefix' => '',
            'disks' => [$backupDisk],
            'continue_on_failure' => false,
        ],

        'temporary_directory' => storage_path('app/backup-temp'),
        'password' => null,
        'encryption' => 'none',
        'verify_backup' => true,
        'tries' => 1,
        'retry_delay' => 0,
    ],

    'notifications' => [
        'notifications' => [],

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => 'noreply@example.com',
            'from' => [
                'address' => 'noreply@example.com',
                'name' => 'WeblexAI',
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],

        'webhook' => [
            'url' => '',
        ],
    ],

    'log_channel' => null,

    'monitor' => [
        'maximum_age_in_days' => 7,
    ],

    'monitor_backups' => [
        [
            'name' => $backupName,
            'disks' => [$backupDisk],
            'health_checks' => [
                MaximumAgeInDays::class => 7,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => null,
        ],

        'tries' => 1,
        'retry_delay' => 0,
    ],
];
