<?php

use App\Enums\ModelStatus;
use App\Models\Language;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function translationApiFixture(): array
{
    $owner = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $source = Language::query()->create([
        'name' => 'English',
        'country_name' => 'United States',
        'iso_2' => 'en',
        'iso_3' => 'eng',
        'is_active' => ModelStatus::ACTIVE,
    ]);
    $target = Language::query()->create([
        'name' => 'French',
        'country_name' => 'France',
        'iso_2' => 'fr',
        'iso_3' => 'fra',
        'is_active' => ModelStatus::ACTIVE,
    ]);
    $apiKey = 'test-api-key';
    $projectId = DB::table('projects')->insertGetId([
        'uuid' => Str::uuid()->toString(),
        'user_id' => $owner->id,
        'name' => 'Test project',
        'slug' => 'test-project',
        'api_key_hash' => hash('sha256', $apiKey),
        'original_language_id' => $source->id,
        'is_active' => ModelStatus::ACTIVE->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $project = Project::findOrFail($projectId);
    $project->languages()->attach($target->id, [
        'is_public' => true,
        'should_display_automatics' => true,
        'is_disabled' => false,
    ]);
    $project->acceptedOrigins()->create(['origin' => 'https://example.com']);

    return compact('owner', 'project', 'source', 'target', 'apiKey');
}

function translationApiHeaders(string $apiKey, string $origin = 'https://example.com'): array
{
    return [
        'Authorization' => 'Bearer '.$apiKey,
        'Origin' => $origin,
        'X-Page-Url' => $origin.'/products',
        'X-Page-Title' => 'Products',
        'Accept' => 'application/json',
    ];
}

it('returns project config for an accepted exact origin', function () {
    $fixture = translationApiFixture();

    $this->withHeaders(translationApiHeaders($fixture['apiKey']))
        ->getJson('/api/project/config')
        ->assertOk()
        ->assertHeader('X-Weblex-Contract', '1')
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.original_language.iso_2', 'en')
        ->assertJsonPath('data.languages.0.iso_2', 'fr');
});

it('only exposes public enabled project languages in browser config', function () {
    $fixture = translationApiFixture();
    $private = Language::query()->create([
        'name' => 'Spanish',
        'country_name' => 'Spain',
        'iso_2' => 'es',
        'iso_3' => 'spa',
        'is_active' => ModelStatus::ACTIVE,
    ]);
    $disabled = Language::query()->create([
        'name' => 'German',
        'country_name' => 'Germany',
        'iso_2' => 'de',
        'iso_3' => 'deu',
        'is_active' => ModelStatus::ACTIVE,
    ]);

    $fixture['project']->languages()->attach($private->id, [
        'is_public' => false,
        'should_display_automatics' => true,
        'is_disabled' => false,
    ]);
    $fixture['project']->languages()->attach($disabled->id, [
        'is_public' => true,
        'should_display_automatics' => true,
        'is_disabled' => true,
    ]);

    $response = $this->withHeaders(translationApiHeaders($fixture['apiKey']))
        ->getJson('/api/project/config')
        ->assertOk();

    expect(collect($response->json('data.languages'))->pluck('iso_2')->all())
        ->toBe(['fr', 'en']);
});

it('rejects translation batches over the public request limit', function () {
    $fixture = translationApiFixture();

    $this->withHeaders(translationApiHeaders($fixture['apiKey']))
        ->postJson('/api/project/translations', [
            'source' => 'en',
            'target' => 'fr',
            'translatables' => collect(range(1, 101))
                ->map(fn (int $id): array => ['id' => $id, 'text' => "Text {$id}"])
                ->all(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('translatables');
});

it('uses the same response for every project authentication failure', function (array $headers) {
    translationApiFixture();

    $this->withHeaders($headers)
        ->getJson('/api/project/config')
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
})->with([
    'missing bearer token' => [[
        'Origin' => 'https://example.com',
        'X-Page-Url' => 'https://example.com/products',
    ]],
    'missing origin' => [[
        'Authorization' => 'Bearer test-api-key',
        'X-Page-Url' => 'https://example.com/products',
    ]],
    'incorrect key' => [translationApiHeaders('incorrect-key')],
    'unaccepted origin' => [translationApiHeaders('test-api-key', 'https://evil.example')],
    'page URL origin mismatch' => [[
        ...translationApiHeaders('test-api-key'),
        'X-Page-Url' => 'https://evil.example/products',
    ]],
]);

it('rejects inactive projects and owners without revealing which failed', function (string $inactive) {
    $fixture = translationApiFixture();

    if ($inactive === 'project') {
        $fixture['project']->update(['is_active' => ModelStatus::INACTIVE]);
    } else {
        $fixture['owner']->update(['is_active' => ModelStatus::INACTIVE]);
    }

    $this->withHeaders(translationApiHeaders($fixture['apiKey']))
        ->getJson('/api/project/config')
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
})->with(['project', 'owner']);

it('allows browser preflight without a bearer token', function () {
    $this->withHeaders([
        'Origin' => 'https://example.com',
        'Access-Control-Request-Method' => 'GET',
        'Access-Control-Request-Headers' => 'Authorization, X-Page-Url',
    ])->options('/api/project/config')
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', '*');
});
