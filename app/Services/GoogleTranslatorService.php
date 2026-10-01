<?php

namespace App\Services;

use App\Contracts\TranslationServiceInterface;
use App\Models\Language;
use App\Models\ProviderCredential;
use App\Traits\HasTranslationBatching;
use Google\Cloud\Translate\V3\Client\TranslationServiceClient;
use Google\Cloud\Translate\V3\TranslateTextRequest;
use Google\Cloud\Translate\V3\TranslateTextResponse;

class GoogleTranslatorService implements TranslationServiceInterface
{
    use HasTranslationBatching;

    public function __construct(private ProviderCredential $credential) {}

    public function translateNmt(array $translatables, Language $source, Language $target, array $options = []): array
    {
        $credentials = json_decode((string) $this->credential->service_account, true);

        if (blank($this->credential->google_project_id) || ! is_array($credentials)) {
            throw new \RuntimeException('Google Cloud Translation is not configured.');
        }

        $client = $this->createClient($credentials);
        $batcher = app(ProviderBatchService::class);
        $parts = $batcher->split($translatables);
        $translatedParts = [];
        $emitted = [];

        try {
            foreach ($batcher->batches($parts, 2000, 100, 4096) as $batch) {
                $this->assertDeadline($options);
                $request = (new TranslateTextRequest)
                    ->setParent($client->locationName($this->credential->google_project_id, 'global'))
                    ->setSourceLanguageCode($source->iso_2)
                    ->setTargetLanguageCode($target->iso_2)
                    ->setMimeType('text/plain')
                    ->setContents(array_map(fn (array $item): string => $item['provider_text'] ?? $item['text'], $batch));
                $translations = $this->translateWithRetry($client, $request, $options);

                if (count($translations) !== count($batch)) {
                    throw new \RuntimeException('Google Cloud Translation returned an incomplete response.');
                }

                foreach ($batch as $index => $item) {
                    $translated = $translations[$index]?->getTranslatedText();
                    if (! is_string($translated) || $translated === '') {
                        throw new \RuntimeException('Google Cloud Translation returned an invalid response.');
                    }
                    $this->assertPlaceholders($item['provider_text'] ?? $item['text'], $translated);
                    $translatedParts[] = [
                        'id' => $item['id'],
                        'translated' => $translated,
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
        } finally {
            $client->close();
        }

        $results = $batcher->reassemble($parts, $translatedParts);

        return $results;
    }

    public function translateLlm(
        array $translatables,
        Language $source,
        Language $target,
        array $options = [],
    ): array {
        return $this->translateNmt($translatables, $source, $target, $options);
    }

    protected function createClient(array $credentials): TranslationServiceClient
    {
        return new TranslationServiceClient([
            'credentials' => $credentials,
            'apiEndpoint' => $this->credential->provider->endpoint(),
        ]);
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

    private function assertPlaceholders(string $source, string $translated): void
    {
        preg_match_all('/GLS[0-9A-HJKMNP-TV-Z]{26}/', $source, $matches);
        foreach ($matches[0] as $placeholder) {
            if (! str_contains($translated, $placeholder)) {
                throw new \RuntimeException('Google Cloud Translation lost a glossary placeholder.');
            }
        }
    }

    private function translateWithRetry(TranslationServiceClient $client, TranslateTextRequest $request, array $options): iterable
    {
        $attempts = config('translation.attempts', 2);
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $this->translateText($client, $request, [
                    'timeoutMillis' => (int) ($this->remainingTimeout($options) * 1000),
                    'retrySettings' => ['retriesEnabled' => false],
                ])->getTranslations();
            } catch (\Throwable $exception) {
                $status = method_exists($exception, 'getStatus') ? (string) $exception->getStatus() : '';
                if ($attempt === $attempts || ! in_array($status, ['RESOURCE_EXHAUSTED', 'UNAVAILABLE', 'INTERNAL', 'DEADLINE_EXCEEDED'], true)) {
                    throw $exception;
                }
                $this->remainingTimeout($options);
                usleep(500000);
            }
        }

        throw new \RuntimeException('Google Cloud Translation failed.');
    }

    protected function translateText(TranslationServiceClient $client, TranslateTextRequest $request, array $options): TranslateTextResponse
    {
        return $client->translateText($request, $options);
    }
}
