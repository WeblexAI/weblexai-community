<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TranslationProvider: string implements HasLabel
{
    case GOOGLE = 'google';
    case OPENAI = 'openai';
    case OPENROUTER = 'openrouter';
    case GEMINI = 'gemini';
    case QWEN = 'qwen';

    public function getLabel(): string
    {
        return match ($this) {
            self::GOOGLE => 'Google Cloud Translation',
            self::OPENAI => 'OpenAI',
            self::OPENROUTER => 'OpenRouter',
            self::GEMINI => 'Gemini',
            self::QWEN => 'Qwen',
        };
    }

    public function type(): TranslationModelType
    {
        return in_array($this, [self::GOOGLE, self::QWEN], true)
            ? TranslationModelType::NMT
            : TranslationModelType::LLM;
    }

    public function defaultModel(): ?string
    {
        return match ($this) {
            self::GOOGLE => null,
            self::OPENAI => 'gpt-4.1-mini',
            self::OPENROUTER => 'openai/gpt-4.1-mini',
            self::GEMINI => 'gemini-2.0-flash-lite',
            self::QWEN => 'qwen-mt-flash',
        };
    }

    public function endpoint(): string
    {
        $endpoint = config("ai.providers.{$this->value}.url");

        if (! is_string($endpoint) || blank($endpoint)) {
            throw new \LogicException("The {$this->value} translation provider endpoint is not configured.");
        }

        return $endpoint;
    }
}
