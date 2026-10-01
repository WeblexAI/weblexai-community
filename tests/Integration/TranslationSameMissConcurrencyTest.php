<?php

use App\DTOs\CDN\TranslatedItemDTO;
use App\DTOs\CDN\TranslationContext;
use App\Enums\ModelStatus;
use App\Enums\TranslationProvider;
use App\Models\Language;
use App\Models\Page;
use App\Models\Project;
use App\Models\ProviderCredential;
use App\Models\Translation;
use App\Models\User;
use App\Pipelines\CDN\DetermineTranslationModel;
use App\Pipelines\CDN\ResolveLanguages;
use App\Pipelines\CDN\RunModelTranslations;
use App\Pipelines\CDN\StoreTranslationsInDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    if (! function_exists('pcntl_fork') || config('cache.default') !== 'redis' || config('database.default') !== 'pgsql') {
        $this->markTestSkipped('This integration test requires Linux pcntl with shared PostgreSQL and Redis.');
    }
});

function sameMissFixture(): array
{
    $owner = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $source = Language::query()->create(['name' => 'English', 'country_name' => 'United States', 'iso_2' => 'en', 'iso_3' => 'eng', 'is_active' => ModelStatus::ACTIVE]);
    $target = Language::query()->create(['name' => 'French', 'country_name' => 'France', 'iso_2' => 'fr', 'iso_3' => 'fra', 'is_active' => ModelStatus::ACTIVE]);
    $credential = ProviderCredential::query()->create(['user_id' => $owner->id, 'name' => 'Concurrency provider', 'provider' => TranslationProvider::OPENAI, 'api_key' => 'test-key', 'model' => 'test-model', 'is_active' => true]);
    $project = Project::query()->create(['name' => 'Concurrent '.uniqid(), 'user_id' => $owner->id, 'original_language_id' => $source->id, 'provider_credential_id' => $credential->id, 'is_active' => ModelStatus::ACTIVE, 'should_display_automatics' => true]);
    $project->languages()->attach($target, ['is_public' => true, 'is_disabled' => false, 'should_display_automatics' => true]);
    $page = Page::query()->create(['project_id' => $project->id, 'domain' => 'concurrency.test/', 'origin' => 'https://concurrency.test', 'title' => 'Concurrency']);

    return compact('project', 'page');
}

function sameMissContext(int $projectId, int $pageId): TranslationContext
{
    DB::purge();
    DB::reconnect();
    Cache::clearResolvedInstances();
    $project = Project::query()->findOrFail($projectId);
    $page = Page::query()->findOrFail($pageId);
    $item = ['id' => 'same', 'text' => 'Welcome home', 'type' => 'text', 'attr' => '', 'context' => ''];
    $context = new TranslationContext(['source' => 'en', 'target' => 'fr', 'translatables' => [$item]], $project);
    $context->page = $page;
    (new ResolveLanguages)->handle($context, fn (TranslationContext $context) => $context);
    $context->needsNmtTranslation->push([...$item, 'original_text' => $item['text'], 'original_context' => '', 'total_words' => 2]);
    (new DetermineTranslationModel)->handle($context, fn (TranslationContext $context) => $context);

    return $context;
}

it('runs a simultaneous identical miss once and lets the waiting request reuse the stored translation', function () {
    $fixture = sameMissFixture();
    $counterKey = 'test:provider-executions:'.uniqid();
    $errorKey = 'test:provider-errors:'.uniqid();
    Cache::store('redis')->forget($counterKey);
    Cache::store('redis')->forget($errorKey);
    Http::preventStrayRequests();
    Http::fake(['*' => function () use ($counterKey) {
        Cache::store('redis')->increment($counterKey);
        usleep(300000);

        return Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode(['translations' => [['id' => 'same', 'translated' => 'Bienvenue chez vous']]])]]]]);
    }]);
    $result = tempnam(sys_get_temp_dir(), 'weblex-same-miss-');
    $children = [];
    DB::disconnect();

    foreach (range(1, 2) as $index) {
        $pid = pcntl_fork();
        if ($pid === 0) {
            try {
                $context = sameMissContext($fixture['project']->id, $fixture['page']->id);
                app(RunModelTranslations::class)->handle($context, fn (TranslationContext $context) => $context);
                file_put_contents($result.'.'.$index, json_encode(['result' => $context->translatedItems->sole()->translated]));
                exit(0);
            } catch (Throwable $exception) {
                Redis::rpush($errorKey, $exception::class.': '.$exception->getMessage());
                file_put_contents($result.'.'.$index, json_encode(['error' => $exception->getMessage()]));
                exit(1);
            }
        }
        $children[] = $pid;
    }
    $statuses = [];
    foreach ($children as $pid) {
        pcntl_waitpid($pid, $status);
        $statuses[] = pcntl_wexitstatus($status);
    }
    DB::reconnect();
    $childResults = collect([1, 2])->map(fn (int $index): array => json_decode((string) file_get_contents($result.'.'.$index), true));

    expect(Redis::lrange($errorKey, 0, -1))->toBe([])
        ->and($statuses)->toBe([0, 0])
        ->and((int) Cache::store('redis')->get($counterKey))->toBe(1)
        ->and(Translation::query()->where('project_id', $fixture['project']->id)->count())->toBe(1)
        ->and($childResults->pluck('result')->all())->toBe(['Bienvenue chez vous', 'Bienvenue chez vous']);
    unlink($result.'.1');
    unlink($result.'.2');
    unlink($result);
});

it('refuses to persist a provider result after the generation revision changes', function () {
    $fixture = sameMissFixture();
    $context = sameMissContext($fixture['project']->id, $fixture['page']->id);
    $context->nmtTranslated->push(new TranslatedItemDTO('same', 'Welcome home', 'Bienvenue chez vous', 'nmt'));
    Project::query()->whereKey($fixture['project']->id)->increment('generation_revision');

    expect(fn () => (new StoreTranslationsInDatabase)->handle($context, fn (TranslationContext $context) => $context))
        ->toThrow(RuntimeException::class, 'configuration changed')
        ->and(Translation::query()->where('project_id', $fixture['project']->id)->count())->toBe(0);
});
