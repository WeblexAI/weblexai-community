<?php

namespace App\Services;

use App\Contracts\TranslationServiceInterface;
use App\Models\Language;
use App\Models\ProviderCredential;
use App\Traits\HasTranslationBatching;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

abstract class AbstractOpenAiCompatibleTranslationService implements TranslationServiceInterface
{
    use HasTranslationBatching;

    public function __construct(protected ProviderCredential $credential) {}

    abstract protected function providerConfig(): array;

    public function translateNmt(array $translatables, Language $source, Language $target): array
    {
        return $this->translateLlm($translatables, $source, $target);
    }

    public function translateLlm(
        array $translatables,
        Language $source,
        Language $target,
        array $options = [],
    ): array {
        $config = $this->providerConfig();
        $this->assertConfigured($config);
        $results = [];

        foreach ($this->packIntoCharBatches($translatables, $config['max_chars']) as $batch) {
            $response = $this->client($config)
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
                ->throw()
                ->json('choices.0.message.content');

            $translations = $this->decodeTranslations((string) $response);

            foreach ($batch as $index => $item) {
                $results[] = [
                    'id' => $item['id'],
                    'text' => $item['text'],
                    'translated' => $translations[$index]['translated'] ?? $item['text'],
                ];
            }
        }

        return $results;
    }

    private function client(array $config): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->withToken($config['api_key'])
            ->timeout($config['timeout'])
            ->retry(2, 500);

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
            fn (array $item): array => ['text' => $item['text']],
            $batch,
        );

        return sprintf(
            'Translate each item from %s to %s. Preserve HTML, placeholders, whitespace intent, and order. '.
            'Return only JSON as {"translations":[{"translated":"..."}]}. Context: %s. Items: %s',
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
}
