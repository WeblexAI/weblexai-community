<?php

use App\Enums\TranslationProvider;
use App\Models\Language;
use App\Models\ProviderCredential;
use App\Services\AbstractOpenAiCompatibleTranslationService;
use App\Services\GeminiTranslationService;
use App\Services\GoogleTranslatorService;
use App\Services\ProviderBatchService;
use Google\Cloud\Translate\V3\Client\TranslationServiceClient;
use Google\Cloud\Translate\V3\TranslateTextRequest;
use Google\Cloud\Translate\V3\TranslateTextResponse;
use Google\Cloud\Translate\V3\Translation;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

it('rejects incomplete and duplicate stable ids from compatible providers', function () {
    $service = new class extends AbstractOpenAiCompatibleTranslationService
    {
        public function __construct() {}

        protected function providerConfig(): array
        {
            return [];
        }

        public function validate(array $translations, array $batch): array
        {
            return $this->validateTranslations($translations, $batch);
        }
    };
    $batch = [
        ['id' => 'a', 'text' => 'One'],
        ['id' => 'b', 'text' => 'Two'],
    ];

    expect(fn () => $service->validate([['id' => 'a', 'translated' => 'Un']], $batch))
        ->toThrow(RuntimeException::class, 'incomplete')
        ->and(fn () => $service->validate([
            ['id' => 'a', 'translated' => 'Un'],
            ['id' => 'a', 'translated' => 'Deux'],
        ], $batch))->toThrow(RuntimeException::class, 'invalid');
});

it('uses stable reordered OpenAI-compatible ids and rejects truncated output', function () {
    $service = new class extends AbstractOpenAiCompatibleTranslationService
    {
        public function __construct() {}

        protected function providerConfig(): array
        {
            return ['api_key' => 'test', 'base_uri' => 'https://provider.test/v1', 'model' => 'test', 'timeout' => 30, 'max_chars' => 2000];
        }
    };
    $source = new Language(['name' => 'English']);
    $target = new Language(['name' => 'French']);
    Http::fake([
        'provider.test/*' => Http::response([
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => json_encode(['translations' => [
                    ['id' => 'b', 'translated' => 'Deux'],
                    ['id' => 'a', 'translated' => 'Un'],
                ]])],
            ]],
        ]),
    ]);

    $result = $service->translateLlm([
        ['id' => 'a', 'text' => 'One'],
        ['id' => 'b', 'text' => 'Two'],
    ], $source, $target);

    expect($result)->toMatchArray([
        ['id' => 'a', 'text' => 'One', 'translated' => 'Un', 'type' => 'text', 'attr' => '', 'context' => ''],
        ['id' => 'b', 'text' => 'Two', 'translated' => 'Deux', 'type' => 'text', 'attr' => '', 'context' => ''],
    ]);

});

it('rejects truncated OpenAI-compatible responses', function () {
    Http::swap(new Factory);
    $service = new class extends AbstractOpenAiCompatibleTranslationService
    {
        public function __construct() {}

        protected function providerConfig(): array
        {
            return ['api_key' => 'test', 'base_uri' => 'https://provider.test/v1', 'model' => 'test', 'timeout' => 30, 'max_chars' => 2000];
        }
    };
    Http::fake(['provider.test/*' => Http::response(['choices' => [[
        'finish_reason' => 'length',
        'message' => ['content' => '{}'],
    ]]])]);

    expect(fn () => $service->translateLlm(
        [['id' => 'a', 'text' => 'One']],
        new Language(['name' => 'English']),
        new Language(['name' => 'French']),
    ))->toThrow(RuntimeException::class, 'truncated');
});

it('does not start an OpenAI-compatible request after its deadline', function (string $method) {
    Http::swap(new Factory);
    $service = new class extends AbstractOpenAiCompatibleTranslationService
    {
        public function __construct() {}

        protected function providerConfig(): array
        {
            return ['api_key' => 'test', 'base_uri' => 'https://provider.test/v1', 'model' => 'test', 'timeout' => 30, 'max_chars' => 2000];
        }
    };

    expect(fn () => $service->{$method}(
        [['id' => 'a', 'text' => 'One']],
        new Language(['name' => 'English']),
        new Language(['name' => 'French']),
        ['deadline_at' => microtime(true) - 1],
    ))->toThrow(RuntimeException::class, 'deadline');

    Http::assertNothingSent();
})->with(['translateLlm', 'translateNmt']);

it('rejects incomplete, duplicate, unexpected, and empty provider items', function (array $translations) {
    $service = new class extends AbstractOpenAiCompatibleTranslationService
    {
        public function __construct() {}

        protected function providerConfig(): array
        {
            return [];
        }

        public function validate(array $translations): array
        {
            return $this->validateTranslations($translations, [
                ['id' => 'a', 'text' => 'One'],
                ['id' => 'b', 'text' => 'Two'],
            ]);
        }
    };

    expect(fn () => $service->validate($translations))->toThrow(RuntimeException::class);
})->with([
    'incomplete' => [[['id' => 'a', 'translated' => 'Un']]],
    'duplicate' => [[['id' => 'a', 'translated' => 'Un'], ['id' => 'a', 'translated' => 'Deux']]],
    'unexpected' => [[['id' => 'a', 'translated' => 'Un'], ['id' => 'x', 'translated' => 'Deux']]],
    'empty' => [[['id' => 'a', 'translated' => 'Un'], ['id' => 'b', 'translated' => '']]],
]);

it('splits and reassembles long source items for Google and Gemini provider batches', function () {
    $batcher = app(ProviderBatchService::class);
    $items = [[
        'id' => 'headline',
        'text' => str_repeat('Sentence. ', 600),
        'type' => 'text',
        'attr' => '',
        'context' => '',
    ]];

    $parts = $batcher->split($items);
    $googleBatches = $batcher->batches($parts, 2000);
    $geminiBatches = $batcher->batches($parts, 2000);
    $translated = array_map(fn (array $part): array => [
        'id' => $part['id'],
        'translated' => "[{$part['part']}]",
    ], $parts);

    $results = $batcher->reassemble($parts, $translated);

    expect(count($parts))->toBeGreaterThan(1)
        ->and(collect($googleBatches)->flatten(1))->toHaveCount(count($parts))
        ->and(collect($geminiBatches)->flatten(1))->toHaveCount(count($parts))
        ->and($results)->toHaveCount(1)
        ->and($results[0]['id'])->toBe('headline')
        ->and($results[0]['translated'])->toContain('[0]');
});

it('preserves Unicode whitespace and glossary placeholders at provider split boundaries', function () {
    $batcher = app(ProviderBatchService::class);
    $placeholder = 'GLS01J9K4F5G6H7J8K9M0N1P2Q3R4';
    $text = str_repeat('é ', 990).$placeholder.' '.str_repeat('z', 1100);

    $parts = $batcher->split([['id' => 'unicode', 'text' => $text]], 2000);
    $joined = implode('', array_column($parts, 'provider_text'));

    expect($joined)->toBe($text)
        ->and(collect($parts)->filter(fn (array $part): bool => str_contains($part['provider_text'], $placeholder)))->toHaveCount(1);
});

it('rejects Gemini responses that remove glossary placeholders', function () {
    $service = new GeminiTranslationService(new ProviderCredential([
        'provider' => TranslationProvider::GEMINI,
        'api_key' => 'test',
    ]));
    $method = new ReflectionMethod($service, 'validateTranslations');

    expect(fn () => $method->invoke($service, [
        ['id' => 'a', 'translated' => 'Bonjour'],
    ], [[
        'id' => 'a',
        'text' => 'GLS01J9K4F5G6H7J8K9M0N1P2Q3R4',
    ]]))->toThrow(RuntimeException::class, 'placeholder');
});

it('retries Google unavailable responses once with bounded native client options', function () {
    $requests = [];
    $service = googleServiceWithResponses([
        new class extends RuntimeException
        {
            public function getStatus(): string
            {
                return 'UNAVAILABLE';
            }
        },
        new TranslateTextResponse(['translations' => [new Translation(['translated_text' => 'Un'])]]),
    ], $requests);
    $result = $service->translateNmt(
        [['id' => 'a', 'text' => 'One']],
        new Language(['name' => 'English', 'iso_2' => 'en']),
        new Language(['name' => 'French', 'iso_2' => 'fr']),
        ['deadline_at' => microtime(true) + 10],
    );

    expect($result[0]['translated'])->toBe('Un')
        ->and($requests)->toHaveCount(2)
        ->and($requests[0]['request']->getMimeType())->toBe('text/plain')
        ->and(iterator_to_array($requests[0]['request']->getContents()))->toBe(['One'])
        ->and($requests[0]['options']['retrySettings']['retriesEnabled'])->toBeFalse()
        ->and($requests[0]['options']['timeoutMillis'])->toBeLessThanOrEqual(30_000);
});

it('rejects incomplete Google result cardinality', function () {
    $requests = [];
    $service = googleServiceWithResponses([new TranslateTextResponse([
        'translations' => [new Translation(['translated_text' => 'Un'])],
    ])], $requests);

    expect(fn () => $service->translateNmt([
        ['id' => 'a', 'text' => 'One'],
        ['id' => 'b', 'text' => 'Two'],
    ], new Language(['name' => 'English', 'iso_2' => 'en']), new Language(['name' => 'French', 'iso_2' => 'fr'])))->toThrow(RuntimeException::class, 'incomplete');
});

it('rejects Google translations that lose glossary placeholders', function () {
    $requests = [];
    $service = googleServiceWithResponses([new TranslateTextResponse([
        'translations' => [new Translation(['translated_text' => 'Bonjour'])],
    ])], $requests);

    expect(fn () => $service->translateNmt([
        ['id' => 'a', 'text' => 'GLS01J9K4F5G6H7J8K9M0N1P2Q3R4'],
    ], new Language(['name' => 'English', 'iso_2' => 'en']), new Language(['name' => 'French', 'iso_2' => 'fr'])))->toThrow(RuntimeException::class, 'placeholder');
});

it('publishes a completed provider batch before a later batch fails', function () {
    Http::swap(new Factory);
    $service = new class extends AbstractOpenAiCompatibleTranslationService
    {
        public function __construct() {}

        protected function providerConfig(): array
        {
            return ['api_key' => 'test', 'base_uri' => 'https://provider.test/v1', 'model' => 'test', 'timeout' => 30, 'max_chars' => 2000, 'max_tokens' => 50];
        }
    };
    Http::fake(['provider.test/*' => Http::sequence()
        ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode(['translations' => [['id' => 'a', 'translated' => 'Un']]])]]]])
        ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => '{}']]]])]);
    $published = [];
    $failure = null;

    try {
        $service->translateLlm([
            ['id' => 'a', 'text' => str_repeat('a', 40)],
            ['id' => 'b', 'text' => str_repeat('b', 40)],
        ], new Language(['name' => 'English']), new Language(['name' => 'French']), [
            'on_batch' => function (array $items) use (&$published): void {
                $published = [...$published, ...$items];
            },
        ]);
    } catch (RuntimeException $exception) {
        $failure = $exception;
    }

    expect($failure ?? null)->toBeInstanceOf(RuntimeException::class);

    Http::assertSentCount(2);

    expect($published)->toHaveCount(1)->and($published[0]['id'])->toBe('a');
});

function googleServiceWithResponses(array $responses, array &$requests): GoogleTranslatorService
{
    $credential = new ProviderCredential([
        'provider' => TranslationProvider::GOOGLE,
        'google_project_id' => 'project-id',
        'service_account' => json_encode(['type' => 'service_account']),
    ]);

    return new class($credential, $responses, $requests) extends GoogleTranslatorService
    {
        public function __construct(ProviderCredential $credential, private array $responses, private array &$requests)
        {
            parent::__construct($credential);
        }

        protected function createClient(array $credentials): TranslationServiceClient
        {
            return new TranslationServiceClient(['credentials' => null]);
        }

        protected function translateText(TranslationServiceClient $client, TranslateTextRequest $request, array $options): TranslateTextResponse
        {
            $this->requests[] = compact('request', 'options');
            $response = array_shift($this->responses);
            if ($response instanceof Throwable) {
                throw $response;
            }

            return $response;
        }
    };
}
