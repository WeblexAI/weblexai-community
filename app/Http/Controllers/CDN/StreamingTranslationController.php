<?php

namespace App\Http\Controllers\CDN;

use App\DTOs\CDN\TranslatedItemDTO;
use App\DTOs\CDN\TranslationContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\CDN\TranslateRequest;
use App\Pipelines\CDN\ApplyGlossariesToText;
use App\Pipelines\CDN\CheckTranslationCache;
use App\Pipelines\CDN\DetermineTranslationModel;
use App\Pipelines\CDN\LogActivity;
use App\Pipelines\CDN\LookupDatabaseTranslations;
use App\Pipelines\CDN\QueueTranslationUsageTracking;
use App\Pipelines\CDN\ResolveLanguages;
use App\Pipelines\CDN\ResolvePage;
use App\Pipelines\CDN\RunModelTranslations;
use App\Pipelines\CDN\StoreTranslationsInCache;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamingTranslationController extends Controller
{
    public function __invoke(TranslateRequest $request): StreamedResponse
    {
        $validated = $request->validated();

        return response()->stream(
            function () use ($validated) {
                $this->streamTranslations($validated, request()->attributes->get('project'));
            },
            200,
            [
                'Content-Type' => 'application/x-ndjson',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }

    protected function streamTranslations(array $validated, $project): void
    {
        $context = new TranslationContext($validated, $project);

        $context->setStreamCallback(function (string $source, $items) use ($context) {
            $this->sendBatch($source, $items, $context);
        });
        try {
            app(Pipeline::class)
                ->send($context)
                ->through([
                    ResolveLanguages::class,
                    ResolvePage::class,
                    CheckTranslationCache::class,
                    LookupDatabaseTranslations::class,
                    StoreTranslationsInCache::class,
                    DetermineTranslationModel::class,
                    ApplyGlossariesToText::class,
                    RunModelTranslations::class,
                    LogActivity::class,
                    QueueTranslationUsageTracking::class,
                ])
                ->thenReturn();

            $this->sendEvent('complete', [
                'total' => $context->translatedItems->count(),
                'withheld_ids' => $context->stoppageClass
                    ? collect($validated['translatables'])->pluck('id')->map(fn ($id): string => (string) $id)->all()
                    : $context->withheldIds->unique()->values()->all(),
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error($e);
            $this->sendEvent('error', ['code' => $this->errorCode($e), 'message' => 'Translation failed.']);
            $this->sendEvent('complete', ['total' => $context->translatedItems->count(), 'success' => false]);
        }
    }

    protected function sendBatch(string $source, $items, $context): void
    {
        $this->sendEvent('batch', [
            'translations' => TranslatedItemDTO::toArray($items),
            'source_lang' => $context->source,
            'target_lang' => $context->target,
            'count' => $items->count(),
        ]);
    }

    protected function sendEvent(string $type, array $data): void
    {
        echo json_encode([
            'type' => $type,
            ...$data,
        ])."\n";

        $this->flush();
    }

    protected function flush(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private function errorCode(\Throwable $exception): string
    {
        return match (true) {
            str_contains($exception->getMessage(), 'configuration changed') => 'stale_generation',
            str_contains($exception->getMessage(), 'deadline') => 'deadline',
            str_contains($exception->getMessage(), 'cancelled') => 'cancelled',
            $exception instanceof ConnectionException => 'provider_timeout',
            $exception instanceof QueryException => 'persistence_failed',
            str_contains($exception->getMessage(), 'truncated'),
            str_contains($exception->getMessage(), 'placeholder'),
            str_contains($exception->getMessage(), 'invalid response'),
            str_contains($exception->getMessage(), 'incomplete response') => 'provider_invalid',
            default => 'provider_failed',
        };
    }
}
