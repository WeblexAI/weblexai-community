<?php

namespace App\Services;

use App\Contracts\TranslationServiceInterface;
use App\Models\Language;
use App\Models\ProviderCredential;
use App\Traits\HasTranslationBatching;
use Illuminate\Support\Facades\Http;

class GeminiTranslationService implements TranslationServiceInterface
{
    use HasTranslationBatching;

    public function __construct(private ProviderCredential $credential) {}

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
        $apiKey = $this->credential->api_key;
        $model = $this->credential->model ?: $this->credential->provider->defaultModel();

        if (blank($apiKey) || blank($model)) {
            throw new \RuntimeException('Gemini is not configured.');
        }

        $results = [];

        foreach ($this->packIntoCharBatches($translatables, 12000) as $batch) {
            $content = Http::acceptJson()
                ->asJson()
                ->timeout(120)
                ->retry(2, 500)
                ->post(
                    sprintf(
                        'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
                        rawurlencode($model),
                        rawurlencode($apiKey),
                    ),
                    [
                        'contents' => [[
                            'parts' => [['text' => $this->prompt($batch, $source, $target, $options)]],
                        ]],
                        'generationConfig' => [
                            'temperature' => 0,
                            'responseMimeType' => 'application/json',
                            'responseSchema' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'translations' => [
                                        'type' => 'ARRAY',
                                        'items' => [
                                            'type' => 'OBJECT',
                                            'properties' => [
                                                'translated' => ['type' => 'STRING'],
                                            ],
                                            'required' => ['translated'],
                                        ],
                                    ],
                                ],
                                'required' => ['translations'],
                            ],
                        ],
                    ],
                )
                ->throw()
                ->json('candidates.0.content.parts.0.text');

            $translations = json_decode((string) $content, true)['translations'] ?? null;

            if (! is_array($translations)) {
                throw new \RuntimeException('Gemini returned an invalid response.');
            }

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

    private function prompt(array $batch, Language $source, Language $target, array $options): string
    {
        return sprintf(
            'Translate each item from %s to %s. Preserve HTML, placeholders, whitespace intent, and order. Context: %s. Items: %s',
            $source->name,
            $target->name,
            json_encode(array_filter($options), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode(array_map(
                fn (array $item): array => ['text' => $item['text']],
                $batch,
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }
}
