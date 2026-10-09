<?php

namespace App\Pipelines\CDN;

use App\Contracts\TranslationServiceInterface;
use App\DTOs\CDN\TranslatedItemDTO;
use App\DTOs\CDN\TranslationContext;
use App\Enums\TranslationModelType;
use App\Enums\TranslationProvider;
use App\Services\CDN\TranslationLeaseService;
use App\Services\GeminiTranslationService;
use App\Services\GoogleTranslatorService;
use App\Services\OpenAiTranslationService;
use App\Services\OpenRouterTranslationService;
use App\Services\QwenTranslationService;
use Closure;
use Illuminate\Pipeline\Pipeline;

class RunModelTranslations
{
    public function __construct(private readonly TranslationLeaseService $leases) {}

    public function handle(TranslationContext $context, Closure $next)
    {
        if ($context->needsNmtTranslation->isEmpty()) {
            return $next($context);
        }

        try {
            while (true) {
                $this->assertAvailable($context);
                $this->recheckStored($context);
                if ($context->needsNmtTranslation->isEmpty()) {
                    return $next($context);
                }

                $locks = $this->leases->acquireMany($this->leaseKeys($context));
                if ($locks !== null) {
                    $context->translationLeases = $locks;
                    break;
                }
                usleep(50000);
            }

            $this->recheckStored($context);
            if ($context->needsNmtTranslation->isEmpty()) {
                return $next($context);
            }

            while (($context->credentialLease = $this->leases->acquireCredentialSlot($context->project->provider_credential_id)) === null) {
                $this->assertAvailable($context);
                usleep(50000);
            }

            app(ApplyGlossariesToText::class)->handle($context, fn ($value) => $value);
            $service = $this->resolveTranslationService($context);
            $groups = $context->needsNmtTranslation->groupBy(fn (array $item): string => $this->identity($context, $item));
            $items = $groups->map(fn ($group): array => $group->first())->values()->all();
            $emitted = [];
            $publish = function (array $results) use ($context, $groups, &$emitted): void {
                $this->assertAvailable($context);
                $context->nmtTranslated = collect();
                foreach ($results as $result) {
                    $representative = $context->needsNmtTranslation->first(fn (array $item): bool => (string) $item['id'] === (string) $result['id']);
                    if (! $representative || isset($emitted[(string) $result['id']])) {
                        throw new \RuntimeException('The translation provider returned an invalid response.');
                    }
                    $emitted[(string) $result['id']] = true;
                    foreach ($groups->get($this->identity($context, $representative)) as $item) {
                        $context->nmtTranslated->push(new TranslatedItemDTO(
                            id: (string) $item['id'],
                            text: $item['text'],
                            translated: (string) $result['translated'],
                            source: 'nmt',
                            type: $item['type'],
                            attr: $item['attr'],
                            context: $item['context'],
                        ));
                    }
                }
                app(Pipeline::class)->send($context)->through([
                    ReplaceGlossaryPlaceholders::class,
                    StoreTranslationsInDatabase::class,
                    StoreTranslationsInCache::class,
                ])->thenReturn();
                $context->nmtTranslated = collect();
            };
            $options = [...$context->llmOptions, 'deadline_at' => (float) $context->deadlineAt->format('U.u'), 'on_batch' => $publish];
            $results = $context->useModel === TranslationModelType::LLM
                ? $service->translateLlm($items, $context->sourceLanguage, $context->targetLanguage, $options)
                : $service->translateNmt($items, $context->sourceLanguage, $context->targetLanguage, $options);
            if ($emitted === []) {
                $publish($results);
            }
            if (count($emitted) !== count($items)) {
                throw new \RuntimeException('The translation provider returned an incomplete response.');
            }
        } finally {
            $this->leases->releaseMany($context->translationLeases);
            $this->leases->release($context->credentialLease);
            $context->translationLeases = [];
            $context->credentialLease = null;
        }

        return $next($context);
    }

    private function assertAvailable(TranslationContext $context): void
    {
        if (now()->gte($context->deadlineAt)) {
            throw new \RuntimeException('The translation deadline was exceeded.');
        }
        if (connection_aborted()) {
            throw new \RuntimeException('The translation request was cancelled.');
        }
    }

    private function recheckStored(TranslationContext $context): void
    {
        $previousHits = $context->dbHits;
        $context->dbHits = collect();
        $context->needsDbLookup = $context->needsNmtTranslation;
        $context->needsNmtTranslation = collect();
        app(LookupDatabaseTranslations::class)->handle($context, fn ($value) => $value);
        app(StoreTranslationsInCache::class)->handle($context, fn ($value) => $value);
        $context->dbHits = $previousHits->concat($context->dbHits);
    }

    private function resolveTranslationService(TranslationContext $context): TranslationServiceInterface
    {
        $credential = $context->project->providerCredential;
        if (! $credential || ! $credential->is_active) {
            throw new \RuntimeException('No active translation provider is assigned to this project.');
        }

        return match ($credential->provider) {
            TranslationProvider::GOOGLE => new GoogleTranslatorService($credential),
            TranslationProvider::OPENAI, TranslationProvider::OPENAI_COMPATIBLE => new OpenAiTranslationService($credential),
            TranslationProvider::OPENROUTER => new OpenRouterTranslationService($credential),
            TranslationProvider::GEMINI => new GeminiTranslationService($credential),
            TranslationProvider::QWEN => new QwenTranslationService($credential),
        };
    }

    private function identity(TranslationContext $context, array $item): string
    {
        return implode(':', [
            $context->project->id, $context->page->id, $context->targetLanguage->id,
            $item['type'], $item['attr'], $context->getContextHash($item['context']), $context->getTextHash($item['text']),
        ]);
    }

    private function leaseKeys(TranslationContext $context): array
    {
        return $context->needsNmtTranslation->map(fn (array $item): string => $this->identity($context, $item))->all();
    }
}
