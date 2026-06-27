<?php

use App\Support\ErrorReporting\RemoteExceptionReporter;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    Http::preventStrayRequests();
});

it('does not send reports unless explicitly enabled', function () {
    config([
        'error-reporting.enabled' => false,
        'error-reporting.webhook_url' => 'https://errors.example.test/report',
    ]);

    app(RemoteExceptionReporter::class)->report(new RuntimeException('Test failure'));

    Http::assertNothingSent();
});

it('sends signed webhook reports without sensitive request data', function () {
    config([
        'error-reporting.enabled' => true,
        'error-reporting.webhook_url' => 'https://errors.example.test/report',
        'error-reporting.webhook_secret' => 'signing-secret',
        'error-reporting.telegram_bot_token' => null,
        'error-reporting.telegram_chat_id' => null,
        'error-reporting.throttle_minutes' => 15,
    ]);
    Http::fake(['https://errors.example.test/report' => Http::response()]);

    $exception = new RuntimeException('token=should-not-leak');
    app(RemoteExceptionReporter::class)->report($exception);

    Http::assertSent(function (Request $request): bool {
        $payload = $request->data();
        $signature = 'sha256='.hash_hmac('sha256', $request->body(), 'signing-secret');

        return $request->url() === 'https://errors.example.test/report'
            && $request->hasHeader('X-WeblexAI-Signature', $signature)
            && $payload['schema'] === 'weblexai.error.v1'
            && $payload['exception']['message'] === 'token=[redacted]'
            && ! array_key_exists('headers', $payload['request'] ?? [])
            && ! array_key_exists('body', $payload['request'] ?? []);
    });
});

it('sends Telegram reports and throttles duplicate exceptions', function () {
    config([
        'error-reporting.enabled' => true,
        'error-reporting.webhook_url' => null,
        'error-reporting.telegram_bot_token' => 'test-bot-token',
        'error-reporting.telegram_chat_id' => '123456',
        'error-reporting.throttle_minutes' => 15,
    ]);
    Http::fake(['https://api.telegram.org/*' => Http::response()]);
    $exception = new RuntimeException('A repeated failure');
    $reporter = app(RemoteExceptionReporter::class);

    $reporter->report($exception);
    $reporter->report($exception);

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request['chat_id'] === '123456'
        && str_contains($request['text'], RuntimeException::class));
});
