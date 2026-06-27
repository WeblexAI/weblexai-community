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
use App\Pipelines\CDN\ReplaceGlossaryPlaceholders;
use App\Pipelines\CDN\ResolveLanguages;
use App\Pipelines\CDN\ResolvePage;
use App\Pipelines\CDN\RunModelTranslations;
use App\Pipelines\CDN\StoreTranslationsInCache;
use App\Pipelines\CDN\StoreTranslationsInDatabase;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamingTranslationController extends Controller
{
    public function __invoke(TranslateRequest $request): StreamedResponse
    {
        $validated = $request->validated();
        foreach ($validated['translatables'] as $index => $item) {
            $validated['translatables'][$index]['text'] = html_entity_decode(
                $item['text'],
                ENT_QUOTES | ENT_HTML5,
                'UTF-8',
            );
        }

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
                    DetermineTranslationModel::class,
                    ApplyGlossariesToText::class,
                    RunModelTranslations::class,
                    ReplaceGlossaryPlaceholders::class,
                    StoreTranslationsInDatabase::class,
                    StoreTranslationsInCache::class,
                    LogActivity::class,
                    QueueTranslationUsageTracking::class,
                ])
                ->thenReturn();

            $this->sendEvent('complete', ['total' => $context->translatedItems->count()]);
        } catch (\Throwable $e) {
            Log::error($e);
            $this->sendEvent('error', ['message' => 'Translation failed.']);
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
}
