<?php

namespace App\Support\ErrorReporting;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class RemoteExceptionReporter
{
    public function __construct(private readonly Factory $http) {}

    public function report(Throwable $exception): void
    {
        if (! config('error-reporting.enabled')) {
            return;
        }

        $webhookUrl = trim((string) config('error-reporting.webhook_url'));
        $telegramToken = trim((string) config('error-reporting.telegram_bot_token'));
        $telegramChatId = trim((string) config('error-reporting.telegram_chat_id'));

        if ($webhookUrl === '' && ($telegramToken === '' || $telegramChatId === '')) {
            return;
        }

        $fingerprint = hash('sha256', implode('|', [
            $exception::class,
            $exception->getFile(),
            (string) $exception->getLine(),
        ]));

        if ($this->wasRecentlyReported($fingerprint)) {
            return;
        }

        $payload = $this->payload($exception, $fingerprint);

        try {
            if ($webhookUrl !== '') {
                $this->sendWebhook($webhookUrl, $payload);
            }

            if ($telegramToken !== '' && $telegramChatId !== '') {
                $this->sendTelegram($telegramToken, $telegramChatId, $payload);
            }
        } catch (Throwable) {
        }
    }

    private function wasRecentlyReported(string $fingerprint): bool
    {
        try {
            return ! Cache::add(
                "remote-error-report:{$fingerprint}",
                true,
                now()->addMinutes(max(1, (int) config('error-reporting.throttle_minutes'))),
            );
        } catch (Throwable) {
            return false;
        }
    }

    private function payload(Throwable $exception, string $fingerprint): array
    {
        $request = app()->bound('request') && ! app()->runningInConsole()
            ? request()
            : null;

        return [
            'schema' => 'weblexai.error.v1',
            'occurred_at' => now()->toIso8601String(),
            'fingerprint' => $fingerprint,
            'application' => [
                'name' => config('app.name'),
                'version' => config('community.version'),
                'environment' => app()->environment(),
                'laravel' => app()->version(),
                'php' => PHP_VERSION,
            ],
            'exception' => [
                'class' => $exception::class,
                'message' => $this->sanitize($exception->getMessage()),
                'file' => $this->relativePath($exception->getFile()),
                'line' => $exception->getLine(),
                'trace' => $this->applicationTrace($exception),
            ],
            'request' => $request ? [
                'method' => $request->method(),
                'path' => '/'.ltrim($request->path(), '/'),
            ] : null,
        ];
    }

    private function sendWebhook(string $url, array $payload): void
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $secret = (string) config('error-reporting.webhook_secret');
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'WeblexAI-Error-Reporter/'.config('community.version'),
        ];

        if ($secret !== '') {
            $headers['X-WeblexAI-Signature'] = 'sha256='.hash_hmac('sha256', $json, $secret);
        }

        $this->http
            ->withHeaders($headers)
            ->connectTimeout(min(3, $this->timeout()))
            ->timeout($this->timeout())
            ->withBody($json, 'application/json')
            ->post($url);
    }

    private function sendTelegram(string $token, string $chatId, array $payload): void
    {
        $exception = $payload['exception'];
        $application = $payload['application'];
        $request = $payload['request'];
        $lines = [
            'WeblexAI error',
            "{$application['name']} {$application['version']} ({$application['environment']})",
            "{$exception['class']}: {$exception['message']}",
            "{$exception['file']}:{$exception['line']}",
        ];

        if ($request) {
            $lines[] = "{$request['method']} {$request['path']}";
        }

        $lines[] = "Fingerprint: {$payload['fingerprint']}";

        $this->http
            ->connectTimeout(min(3, $this->timeout()))
            ->timeout($this->timeout())
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => Str::limit(implode("\n", $lines), 3900),
                'disable_notification' => true,
            ]);
    }

    private function applicationTrace(Throwable $exception): array
    {
        return collect($exception->getTrace())
            ->filter(fn (array $frame): bool => isset($frame['file'])
                && str_starts_with($frame['file'], base_path())
                && ! str_contains($frame['file'], DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR))
            ->take(8)
            ->map(fn (array $frame): array => [
                'file' => $this->relativePath($frame['file']),
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
            ])
            ->values()
            ->all();
    }

    private function relativePath(string $path): string
    {
        $basePath = rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($path, $basePath)
            ? str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($basePath)))
            : basename($path);
    }

    private function sanitize(string $message): string
    {
        $message = preg_replace(
            '/\b(password|token|secret|api[_-]?key|authorization)(\s*[:=]\s*)([^\s,;]+)/i',
            '$1$2[redacted]',
            $message,
        ) ?? $message;
        $message = preg_replace('/\bBearer\s+\S+/i', 'Bearer [redacted]', $message) ?? $message;

        return Str::limit($message, 1000);
    }

    private function timeout(): int
    {
        return max(1, (int) config('error-reporting.timeout'));
    }
}
