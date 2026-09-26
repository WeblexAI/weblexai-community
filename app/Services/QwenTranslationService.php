<?php

namespace App\Services;

class QwenTranslationService extends AbstractOpenAiCompatibleTranslationService
{
    protected function providerConfig(): array
    {
        return [
            'api_key' => $this->credential->api_key,
            'base_uri' => $this->credential->provider->endpoint(),
            'model' => $this->credential->model ?: $this->credential->provider->defaultModel(),
            'max_tokens' => 2048,
            'temperature' => 0,
            'timeout' => 120,
            'max_chars' => 12000,
        ];
    }
}
