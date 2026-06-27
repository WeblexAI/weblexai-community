<?php

return [
    'enabled' => env('ERROR_REPORTING_ENABLED', false),
    'webhook_url' => env('ERROR_REPORTING_WEBHOOK_URL'),
    'webhook_secret' => env('ERROR_REPORTING_WEBHOOK_SECRET'),
    'telegram_bot_token' => env('ERROR_REPORTING_TELEGRAM_BOT_TOKEN'),
    'telegram_chat_id' => env('ERROR_REPORTING_TELEGRAM_CHAT_ID'),
    'timeout' => (int) env('ERROR_REPORTING_TIMEOUT', 5),
    'throttle_minutes' => (int) env('ERROR_REPORTING_THROTTLE_MINUTES', 15),
];
