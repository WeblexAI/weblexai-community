<?php

namespace App\Services;

use App\Contracts\TranslationServiceInterface;
use App\Models\Language;
use App\Models\ProviderCredential;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

class QwenTranslationService implements TranslationServiceInterface
{
    public function __construct(private ProviderCredential $credential) {}

    public function translateNmt(array $translatables, Language $source, Language $target): array
    {
        $apiKey = $this->credential->api_key;
        $model = $this->credential->model ?: $this->credential->provider->defaultModel();
        $baseUrl = $this->credential->base_url ?: $this->credential->provider->defaultBaseUrl();

        if (blank($apiKey) || blank($model) || blank($baseUrl)) {
            throw new \RuntimeException('Qwen is not configured.');
        }

        $results = [];

        foreach (array_chunk(array_values($translatables), 3) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($apiKey, $baseUrl, $chunk, $model, $source, $target): void {
                foreach ($chunk as $index => $item) {
                    $pool->as((string) $index)
                        ->withToken($apiKey)
                        ->retry([500, 1000, 2000])
                        ->timeout(120)
                        ->post(rtrim($baseUrl, '/').'/chat/completions', [
                            'model' => $model,
                            'messages' => [['role' => 'user', 'content' => $item['text']]],
                            'translation_options' => [
                                'source_lang' => $source->name,
                                'target_lang' => $target->name,
                            ],
                        ]);
                }
            });

            foreach ($chunk as $index => $item) {
                $response = $responses[(string) $index];

                if ($response instanceof \Throwable) {
                    throw $response;
                }

                $response->throw();
                $results[] = [
                    'id' => $item['id'],
                    'text' => $item['text'],
                    'translated' => $response->json('choices.0.message.content'),
                ];
            }
        }

        return $results;
    }

    public function translateLlm(array $translatables, Language $source, Language $target, array $options = []): array
    {
        return $this->translateNmt($translatables, $source, $target);
    }
}
