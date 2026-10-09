<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TranslationProvider: string implements HasLabel
{
    case GOOGLE = 'google';
    case OPENAI = 'openai';
    case OPENAI_COMPATIBLE = 'openai_compatible';
    case OPENROUTER = 'openrouter';
    case GEMINI = 'gemini';
    case QWEN = 'qwen';

    public function getLabel(): string
    {
        return match ($this) {
            self::GOOGLE => 'Google Cloud Translation',
            self::OPENAI => 'OpenAI',
            self::OPENAI_COMPATIBLE => 'OpenAI-compatible',
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
        return config("ai.providers.{$this->value}.default_model");
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
