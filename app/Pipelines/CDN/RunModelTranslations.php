<?php

namespace App\Pipelines\CDN;

use App\Contracts\TranslationServiceInterface;
use App\DTOs\CDN\TranslatedItemDTO;
use App\DTOs\CDN\TranslationContext;
use App\Enums\TranslationModelType;
use App\Enums\TranslationProvider;
use App\Services\GeminiTranslationService;
use App\Services\GoogleTranslatorService;
use App\Services\OpenAiTranslationService;
use App\Services\OpenRouterTranslationService;
use App\Services\QwenTranslationService;
use Closure;
use Illuminate\Support\Facades\Log;

class RunModelTranslations
{
    public function handle(TranslationContext $context, Closure $next)
    {
        if ($context->needsNmtTranslation->isEmpty()) {
            return $next($context);
        }

        $service = $this->resolveTranslationService($context);

        try {
            $results = $context->useModel === TranslationModelType::LLM
                ? $service->translateLlm(
                    $context->needsNmtTranslation->all(),
                    $context->sourceLanguage,
                    $context->targetLanguage,
                    $context->llmOptions,
                )
                : $service->translateNmt(
                    $context->needsNmtTranslation->all(),
                    $context->sourceLanguage,
                    $context->targetLanguage,
                );

            foreach ($results as $result) {
                $context->nmtTranslated->push(new TranslatedItemDTO(
                    id: (string) $result['id'],
                    text: $result['text'],
                    translated: html_entity_decode(
                        (string) $result['translated'],
                        ENT_QUOTES | ENT_HTML5,
                        'UTF-8',
                    ),
                    source: 'nmt',
                ));
            }
        } catch (\Throwable $exception) {
            Log::error('Translation provider failed.', [
                'exception' => $exception,
                'project_id' => $context->project->id,
                'provider' => $service::class,
            ]);

            throw $exception;
        }

        return $next($context);
    }

    private function resolveTranslationService(TranslationContext $context): TranslationServiceInterface
    {
        $credential = $context->project->providerCredential;

        if (! $credential || ! $credential->is_active) {
            throw new \RuntimeException('No active translation provider is assigned to this project.');
        }

        return match ($credential->provider) {
            TranslationProvider::GOOGLE => new GoogleTranslatorService($credential),
            TranslationProvider::OPENAI => new OpenAiTranslationService($credential),
            TranslationProvider::OPENROUTER => new OpenRouterTranslationService($credential),
            TranslationProvider::GEMINI => new GeminiTranslationService($credential),
            TranslationProvider::QWEN => new QwenTranslationService($credential),
        };
    }
}
