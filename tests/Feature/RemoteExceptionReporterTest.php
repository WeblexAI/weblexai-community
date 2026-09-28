<?php

use App\Settings\ErrorReportingSettings;
use App\Support\ErrorReporting\RemoteExceptionReporter;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    Http::preventStrayRequests();
});

it('does not send reports unless an administrator enables diagnostics', function () {
    $settings = app(ErrorReportingSettings::class);
    $settings->enabled = false;
    $settings->save();

    config(['error-reporting.collector_url' => 'https://errors.example.test/report']);

    app(RemoteExceptionReporter::class)->report(new RuntimeException('Test failure'));

    Http::assertNothingSent();
});

it('sends a sanitized report to the fixed collector', function () {
    $settings = app(ErrorReportingSettings::class);
    $settings->enabled = true;
    $settings->save();

    config(['error-reporting.collector_url' => 'https://errors.example.test/report']);
    Http::fake(['https://errors.example.test/report' => Http::response(['accepted' => true], 202)]);

    app(RemoteExceptionReporter::class)->report(new RuntimeException('token=should-not-leak'));

    Http::assertSent(function (Request $request): bool {
        $payload = $request->data();

        return $request->url() === 'https://errors.example.test/report'
            && $request->hasHeader('X-WeblexAI-Reporter', 'community')
            && $payload['schema'] === 'weblexai.error.v1'
            && $payload['exception']['message'] === 'token=[redacted]'
            && ! array_key_exists('headers', $payload['request'] ?? [])
            && ! array_key_exists('body', $payload['request'] ?? []);
    });
});

it('throttles duplicate exception reports', function () {
    $settings = app(ErrorReportingSettings::class);
    $settings->enabled = true;
    $settings->save();

    config(['error-reporting.collector_url' => 'https://errors.example.test/report']);
    Http::fake(['https://errors.example.test/report' => Http::response(['accepted' => true], 202)]);

    $exception = new RuntimeException('A repeated failure');
    $reporter = app(RemoteExceptionReporter::class);

    $reporter->report($exception);
    $reporter->report($exception);

    Http::assertSentCount(1);
});
