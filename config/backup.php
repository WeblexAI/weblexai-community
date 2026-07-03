<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

$backupName = env('BACKUP_NAME', env('APP_NAME', 'weblexai-community'));
$backupDisk = env('BACKUP_DISK', 'backups');
$backupPath = env('BACKUP_PATH') ?: storage_path('app/backups');
$backupNotificationChannels = env('BACKUP_NOTIFICATIONS_ENABLED', false) ? ['mail'] : [];

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
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => env('BACKUP_ARCHIVE_PASSWORD') ? 'default' : 'none',
        'verify_backup' => true,
        'tries' => 1,
        'retry_delay' => 0,
    ],

    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class => $backupNotificationChannels,
            UnhealthyBackupWasFoundNotification::class => $backupNotificationChannels,
            CleanupHasFailedNotification::class => $backupNotificationChannels,
            BackupWasSuccessfulNotification::class => $backupNotificationChannels,
            HealthyBackupWasFoundNotification::class => $backupNotificationChannels,
            CleanupWasSuccessfulNotification::class => $backupNotificationChannels,
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_EMAIL', 'noreply@example.com'),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'WeblexAI')),
            ],
        ],

        'slack' => [
            'webhook_url' => env('BACKUP_SLACK_WEBHOOK_URL', ''),
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => env('BACKUP_DISCORD_WEBHOOK_URL', ''),
            'username' => '',
            'avatar_url' => '',
        ],

        'webhook' => [
            'url' => env('BACKUP_WEBHOOK_URL', ''),
        ],
    ],

    'log_channel' => null,

    'monitor' => [
        'maximum_age_in_days' => env('BACKUP_MAX_AGE_DAYS', 7),
        'maximum_storage_in_megabytes' => env('BACKUP_MAX_STORAGE_MB', 5120),
    ],

    'monitor_backups' => [
        [
            'name' => $backupName,
            'disks' => [$backupDisk],
            'health_checks' => [
                MaximumAgeInDays::class => env('BACKUP_MAX_AGE_DAYS', 7),
                MaximumStorageInMegabytes::class => env('BACKUP_MAX_STORAGE_MB', 5120),
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
            'delete_oldest_backups_when_using_more_megabytes_than' => env('BACKUP_MAX_STORAGE_MB', 5120),
        ],

        'tries' => 1,
        'retry_delay' => 0,
    ],
];
