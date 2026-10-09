<?php

namespace App\Services;

use App\Contracts\TranslationServiceInterface;
use App\Models\Language;
use App\Models\ProviderCredential;
use App\Traits\HasTranslationBatching;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

abstract class AbstractOpenAiCompatibleTranslationService implements TranslationServiceInterface
{
    use HasTranslationBatching;

    public function __construct(protected ProviderCredential $credential) {}

    abstract protected function providerConfig(): array;

    public function translateNmt(array $translatables, Language $source, Language $target, array $options = []): array
    {
        return $this->translateLlm($translatables, $source, $target, $options);
    }

    public function translateLlm(
        array $translatables,
        Language $source,
        Language $target,
        array $options = [],
    ): array {
        $config = $this->providerConfig();
        $this->assertConfigured($config);
        $translatedParts = [];
        $emitted = [];
        $parts = app(ProviderBatchService::class)->split($translatables);

        $batcher = app(ProviderBatchService::class);
        foreach ($batcher->batches($parts, min(2000, $config['max_chars']), 100, $config['max_tokens'] ?? 4096) as $batch) {
            $this->assertDeadline($options);
            $response = $this->client($config, $options)
                ->post(rtrim($config['base_uri'], '/').'/chat/completions', [
                    'model' => $config['model'],
                    'temperature' => $config['temperature'] ?? 0,
                    'max_tokens' => $config['max_tokens'] ?? 2048,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [[
                        'role' => 'user',
                        'content' => $this->prompt($batch, $source, $target, $options),
                    ]],
                ])
                ->throw();

            $choice = ($response->json('choices') ?? [])[0] ?? [];
            if (($choice['finish_reason'] ?? 'stop') !== 'stop') {
                throw new \RuntimeException('The translation provider truncated its response.');
            }

            $content = $choice['message']['content'] ?? null;

            $translations = $this->decodeTranslations((string) $content);

            $translations = $this->validateTranslations($translations, $batch);

            $translatedParts = [...$translatedParts, ...array_values($translations)];
            $completed = app(ProviderBatchService::class)->completed($parts, $translatedParts, $emitted);
            if ($completed !== [] && isset($options['on_batch']) && is_callable($options['on_batch'])) {
                ($options['on_batch'])($completed);
                foreach ($completed as $item) {
                    $emitted[$item['id']] = true;
                }
            }
        }

        $results = app(ProviderBatchService::class)->reassemble($parts, $translatedParts);

        return $results;
    }

    private function client(array $config, array $options): PendingRequest
    {
        $timeout = $this->remainingTimeout($config['timeout'], $options);

        $request = Http::acceptJson()
            ->asJson()
            ->withToken($config['api_key'])
            ->connectTimeout(min(5, $timeout))
            ->timeout($timeout)
            ->retry(config('translation.attempts', 2), 500, function (\Throwable $exception, PendingRequest $pendingRequest) use ($config, $options): bool {
                try {
                    $pendingRequest->timeout($this->remainingTimeout($config['timeout'], $options));
                } catch (\RuntimeException) {
                    return false;
                }

                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException
                        && ($exception->response->status() === 429 || $exception->response->serverError()));
            });

        foreach ($config['headers'] ?? [] as $name => $value) {
            if ($value !== null && $value !== '') {
                $request->withHeader($name, $value);
            }
        }

        return $request;
    }

    private function assertConfigured(array $config): void
    {
        if (blank($config['api_key'] ?? null) || blank($config['model'] ?? null)) {
            throw new \RuntimeException('The selected translation provider is not configured.');
        }
    }

    private function prompt(array $batch, Language $source, Language $target, array $options): string
    {
        $context = array_filter([
            'website context' => $options['context'] ?? null,
            'tone' => $options['tone'] ?? null,
            'audience' => $options['audience'] ?? null,
        ]);
        $items = array_map(
            fn (array $item): array => [
                'id' => (string) $item['id'],
                'text' => $item['provider_text'] ?? $item['text'],
                'context' => $item['context'] ?? '',
            ],
            $batch,
        );

        return sprintf(
            'Translate each item from %s to %s. Preserve placeholders and whitespace. '.
            'Return only JSON as {"translations":[{"id":"...","translated":"..."}]}. Each supplied id must appear exactly once. Context: %s. Items: %s',
            $source->name,
            $target->name,
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }

    protected function decodeTranslations(string $content): array
    {
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($content));
        $decoded = json_decode($content, true);

        if (! is_array($decoded['translations'] ?? null)) {
            throw new \RuntimeException('The translation provider returned an invalid response.');
        }

        return $decoded['translations'];
    }

    /** @return array<string, array{id: string, translated: string}> */
    protected function validateTranslations(array $translations, array $batch): array
    {
        if (count($translations) !== count($batch)) {
            throw new \RuntimeException('The translation provider returned an incomplete response.');
        }

        $expected = collect($batch)->mapWithKeys(fn (array $item): array => [(string) $item['id'] => $item]);
        $validated = [];
        foreach ($translations as $translation) {
            $id = (string) ($translation['id'] ?? '');
            $translated = $translation['translated'] ?? null;
            if (! isset($expected[$id]) || isset($validated[$id]) || ! is_string($translated) || $translated === '') {
                throw new \RuntimeException('The translation provider returned an invalid response.');
            }

            foreach ($this->placeholders($expected[$id]['provider_text'] ?? $expected[$id]['text']) as $placeholder) {
                if (! str_contains($translated, $placeholder)) {
                    throw new \RuntimeException('The translation provider did not preserve glossary placeholders.');
                }
            }

            $validated[$id] = ['id' => $id, 'translated' => $translated];
        }

        return $validated;
    }

    /** @return array<int, string> */
    private function placeholders(string $text): array
    {
        preg_match_all('/GLS[0-9A-HJKMNP-TV-Z]{26}/', $text, $matches);

        return $matches[0];
    }

    private function assertDeadline(array $options): void
    {
        if (isset($options['deadline_at']) && microtime(true) >= (float) $options['deadline_at']) {
            throw new \RuntimeException('The translation deadline was exceeded.');
        }
    }

    private function remainingTimeout(int $timeout, array $options): float
    {
        $this->assertDeadline($options);

        $remaining = isset($options['deadline_at']) ? (float) $options['deadline_at'] - microtime(true) : null;
        if ($remaining !== null && $remaining < 1) {
            throw new \RuntimeException('The translation deadline was exceeded.');
        }

        return $remaining !== null
            ? min((float) $timeout, $remaining)
            : $timeout;
    }
}
