<?php

namespace App\Services;

use App\Enums\TranslationProvider;

class OpenAiTranslationService extends AbstractOpenAiCompatibleTranslationService
{
    protected function providerConfig(): array
    {
        $baseUrl = $this->credential->provider === TranslationProvider::OPENAI_COMPATIBLE
            ? $this->credential->base_url
            : $this->credential->provider->endpoint();

        return [
            'api_key' => $this->credential->api_key,
            'base_uri' => $baseUrl,
            'body' => strtolower((string) parse_url((string) $baseUrl, PHP_URL_HOST)) === 'api.deepseek.com'
                ? ['thinking' => ['type' => 'disabled']]
                : [],
            'model' => $this->credential->model ?: $this->credential->provider->defaultModel(),
            'max_tokens' => 2048,
            'temperature' => 0,
            'timeout' => config('translation.provider_timeout', 30),
            'max_chars' => 12000,
        ];
    }
}
