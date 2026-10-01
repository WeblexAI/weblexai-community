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

class GeminiTranslationService implements TranslationServiceInterface
{
    use HasTranslationBatching;

    public function __construct(private ProviderCredential $credential) {}

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
        $apiKey = $this->credential->api_key;
        $model = $this->credential->model ?: $this->credential->provider->defaultModel();
        $endpoint = $this->credential->provider->endpoint();

        if (blank($apiKey) || blank($model)) {
            throw new \RuntimeException('Gemini is not configured.');
        }

        $batcher = app(ProviderBatchService::class);
        $parts = $batcher->split($translatables);
        $translatedParts = [];
        $emitted = [];

        $maxOutputTokens = 4096;
        foreach ($batcher->batches($parts, 2000, 100, $maxOutputTokens) as $batch) {
            $this->assertDeadline($options);
            $timeout = $this->remainingTimeout($options);
            $response = Http::acceptJson()
                ->asJson()
                ->connectTimeout(min(5, $timeout))
                ->timeout($timeout)
                ->retry(config('translation.attempts', 2), 500, function (\Throwable $exception, PendingRequest $pendingRequest) use ($options): bool {
                    try {
                        $pendingRequest->timeout($this->remainingTimeout($options));
                    } catch (\RuntimeException) {
                        return false;
                    }

                    return $exception instanceof ConnectionException
                        || ($exception instanceof RequestException
                            && ($exception->response->status() === 429 || $exception->response->serverError()));
                })
                ->post(
                    sprintf(
                        rtrim($endpoint, '/').'/models/%s:generateContent?key=%s',
                        rawurlencode($model),
                        rawurlencode($apiKey),
                    ),
                    [
                        'contents' => [[
                            'parts' => [['text' => $this->prompt($batch, $source, $target, $options)]],
                        ]],
                        'generationConfig' => [
                            'temperature' => 0,
                            'maxOutputTokens' => $maxOutputTokens,
                            'responseMimeType' => 'application/json',
                            'responseSchema' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'translations' => [
                                        'type' => 'ARRAY',
                                        'items' => [
                                            'type' => 'OBJECT',
                                            'properties' => [
                                                'id' => ['type' => 'STRING'],
                                                'translated' => ['type' => 'STRING'],
                                            ],
                                            'required' => ['id', 'translated'],
                                        ],
                                    ],
                                ],
                                'required' => ['translations'],
                            ],
                        ],
                    ],
                )
                ->throw();

            if (data_get($response->json(), 'candidates.0.finishReason', 'STOP') === 'MAX_TOKENS') {
                throw new \RuntimeException('Gemini truncated its response.');
            }

            $content = data_get($response->json(), 'candidates.0.content.parts.0.text');

            $translations = json_decode((string) $content, true)['translations'] ?? null;

            if (! is_array($translations)) {
                throw new \RuntimeException('Gemini returned an invalid response.');
            }

            $translations = $this->validateTranslations($translations, $batch);
            foreach ($batch as $item) {
                $translation = $translations[(string) $item['id']];
                $translatedParts[] = [
                    'id' => $item['id'],
                    'translated' => $translation['translated'],
                ];
            }
            $completed = $batcher->completed($parts, $translatedParts, $emitted);
            if ($completed !== [] && isset($options['on_batch']) && is_callable($options['on_batch'])) {
                ($options['on_batch'])($completed);
                foreach ($completed as $item) {
                    $emitted[$item['id']] = true;
                }
            }
        }

        $results = $batcher->reassemble($parts, $translatedParts);

        return $results;
    }

    private function prompt(array $batch, Language $source, Language $target, array $options): string
    {
        return sprintf(
            'Translate each item from %s to %s. Preserve placeholders and whitespace. Return each supplied id exactly once. Context: %s. Items: %s',
            $source->name,
            $target->name,
            json_encode(array_filter([
                'website context' => $options['context'] ?? null,
                'tone' => $options['tone'] ?? null,
                'audience' => $options['audience'] ?? null,
            ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode(array_map(
                fn (array $item): array => ['id' => (string) $item['id'], 'text' => $item['provider_text'] ?? $item['text'], 'context' => $item['context'] ?? ''],
                $batch,
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }

    /** @return array<string, array{id: string, translated: string}> */
    private function validateTranslations(array $translations, array $batch): array
    {
        if (count($translations) !== count($batch)) {
            throw new \RuntimeException('Gemini returned an incomplete response.');
        }

        $expected = collect($batch)->mapWithKeys(fn (array $item): array => [(string) $item['id'] => $item]);
        $validated = [];
        foreach ($translations as $translation) {
            $id = (string) ($translation['id'] ?? '');
            $translated = $translation['translated'] ?? null;
            if (! isset($expected[$id]) || isset($validated[$id]) || ! is_string($translated) || $translated === '') {
                throw new \RuntimeException('Gemini returned an invalid response.');
            }
            preg_match_all('/GLS[0-9A-HJKMNP-TV-Z]{26}/', $expected[$id]['provider_text'] ?? $expected[$id]['text'], $matches);
            foreach ($matches[0] as $placeholder) {
                if (! str_contains($translated, $placeholder)) {
                    throw new \RuntimeException('Gemini lost a glossary placeholder.');
                }
            }
            $validated[$id] = ['id' => $id, 'translated' => $translated];
        }

        return $validated;
    }

    private function assertDeadline(array $options): void
    {
        if (isset($options['deadline_at']) && microtime(true) >= (float) $options['deadline_at']) {
            throw new \RuntimeException('The translation deadline was exceeded.');
        }
    }

    private function remainingTimeout(array $options): float
    {
        $this->assertDeadline($options);

        $remaining = isset($options['deadline_at']) ? (float) $options['deadline_at'] - microtime(true) : null;
        if ($remaining !== null && $remaining < 1) {
            throw new \RuntimeException('The translation deadline was exceeded.');
        }

        return $remaining !== null
            ? min((float) config('translation.provider_timeout', 30), $remaining)
            : config('translation.provider_timeout', 30);
    }
}
