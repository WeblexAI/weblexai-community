<?php

use App\Enums\TranslationProvider;
use App\Http\Requests\CDN\TranslateRequest;
use App\Models\Language;
use App\Models\Project;
use App\Models\ProviderCredential;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

function streamingTranslationFixture(): array
{
    $owner = User::factory()->create();
    $source = Language::query()->create(['name' => 'English', 'country_name' => 'US', 'iso_2' => 'en', 'iso_3' => 'eng', 'is_active' => 1]);
    $target = Language::query()->create(['name' => 'French', 'country_name' => 'France', 'iso_2' => 'fr', 'iso_3' => 'fra', 'is_active' => 1]);
    $credential = ProviderCredential::query()->create(['name' => 'Mock', 'user_id' => $owner->id, 'provider' => TranslationProvider::OPENAI, 'api_key' => 'mock', 'model' => 'mock', 'is_active' => true]);
    $project = Project::query()->create(['name' => 'Streaming', 'user_id' => $owner->id, 'original_language_id' => $source->id, 'provider_credential_id' => $credential->id, 'is_active' => 1, 'should_display_automatics' => true]);
    $project->languages()->attach($target, ['is_public' => true, 'should_display_automatics' => true, 'is_disabled' => false]);
    $project->acceptedOrigins()->create(['origin' => 'https://example.test']);
    $headers = ['Authorization' => 'Bearer '.$project->api_key, 'Origin' => 'https://example.test', 'X-Page-Url' => 'https://example.test/products', 'X-Page-Title' => 'Products'];

    return compact('project', 'headers');
}

it('persists typed contextual source before streaming and returns every matching item ID on cache hits', function () {
    $fixture = streamingTranslationFixture();
    Http::preventStrayRequests();
    Http::fake(fn ($request) => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode(['translations' => [['id' => 'first', 'translated' => 'Bonjour']]])]]]]));
    $payload = ['source' => 'en', 'target' => 'fr', 'translatables' => [
        ['id' => 'first', 'text' => ' Hello ', 'type' => 'attr', 'attr' => 'title', 'context' => 'Hello world'],
        ['id' => 'second', 'text' => ' Hello ', 'type' => 'attr', 'attr' => 'title', 'context' => 'Hello world'],
    ]];
    foreach (range(1, 2) as $attempt) {
        $response = $this->withHeaders($fixture['headers'])->postJson('/api/project/translations', $payload)->assertOk();
        $events = collect(explode("\n", trim($response->streamedContent())))->map(fn ($line) => json_decode($line, true));
        expect($events->last())->toMatchArray(['type' => 'complete', 'total' => 2, 'success' => true]);
        expect($events->where('type', 'batch')->pluck('translations')->flatten(1)->pluck('id')->all())->toBe(['first', 'second']);
    }
    $row = Translation::query()->sole();
    expect($row->text)->toBe(' Hello ')->and($row->source_context)->toBe('Hello world')->and($row->attr)->toBe('title');
    Http::assertSentCount(1);
});

it('returns explicit withheld IDs for an inactive page without provider work', function () {
    $fixture = streamingTranslationFixture();
    $fixture['project']->pages()->create(['domain' => 'example.test/products', 'origin' => 'https://example.test/products', 'is_active' => false]);
    Http::preventStrayRequests();
    Http::fake();
    $response = $this->withHeaders($fixture['headers'])->postJson('/api/project/translations', ['source' => 'en', 'target' => 'fr', 'translatables' => [['id' => 1, 'text' => 'Hello']]])->assertOk();
    expect(json_decode(trim($response->streamedContent()), true))->toMatchArray(['type' => 'complete', 'total' => 0, 'withheld_ids' => ['1'], 'success' => true]);
    Http::assertNothingSent();
});

it('accepts typed translation metadata and applies legacy defaults', function () {
    $request = TranslateRequest::create('/api/project/translations', 'POST', [
        'source' => 'en',
        'target' => 'fr',
        'translatables' => [['id' => 'heading', 'text' => 'Welcome']],
    ]);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    expect($request->validated('translatables.0'))->toMatchArray([
        'type' => 'text',
        'attr' => '',
        'context' => '',
    ]);
});

it('rejects unsupported attributes and oversized batches', function () {
    $rules = (new TranslateRequest)->rules();
    $payload = [
        'source' => 'en',
        'target' => 'fr',
        'translatables' => collect(range(1, 101))->map(fn (int $id): array => [
            'id' => $id,
            'text' => 'Text',
            'type' => 'attr',
            'attr' => 'href',
            'context' => '',
        ])->all(),
    ];

    $validator = Validator::make($payload, $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('translatables'))->toBeTrue()
        ->and($validator->errors()->has('translatables.0.attr'))->toBeTrue();
});
