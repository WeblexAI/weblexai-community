<?php

use App\DTOs\CDN\TranslatedItemDTO;
use App\DTOs\CDN\TranslationContext;
use App\Enums\GlossaryRule;
use App\Enums\ModelStatus;
use App\Enums\TranslationQuality;
use App\Enums\TranslationType;
use App\Models\Language;
use App\Models\Page;
use App\Models\Project;
use App\Models\Translation;
use App\Models\User;
use App\Pipelines\CDN\ApplyGlossariesToText;
use App\Pipelines\CDN\CheckTranslationCache;
use App\Pipelines\CDN\LookupDatabaseTranslations;
use App\Pipelines\CDN\ResolveLanguages;
use App\Pipelines\CDN\StoreTranslationsInCache;
use App\Pipelines\CDN\StoreTranslationsInDatabase;
use App\Services\Cache\TranslationCacheStore;
use App\Services\GlossaryService;
use App\Support\TextHasher;
use Illuminate\Support\Facades\Cache;

function translationDeliveryFixture(array $projectAttributes = [], array $pivotAttributes = []): array
{
    $owner = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $source = Language::query()->create(['name' => 'English', 'country_name' => 'United States', 'iso_2' => 'en', 'iso_3' => 'eng', 'is_active' => ModelStatus::ACTIVE]);
    $target = Language::query()->create(['name' => 'French', 'country_name' => 'France', 'iso_2' => 'fr', 'iso_3' => 'fra', 'is_active' => ModelStatus::ACTIVE]);
    $project = Project::query()->create(['name' => 'Delivery '.uniqid(), 'user_id' => $owner->id, 'original_language_id' => $source->id, 'is_active' => ModelStatus::ACTIVE, 'should_display_automatics' => true, ...$projectAttributes]);
    $project->languages()->attach($target, ['is_public' => true, 'is_disabled' => false, 'should_display_automatics' => true, ...$pivotAttributes]);
    $page = Page::query()->create(['project_id' => $project->id, 'domain' => 'example.test/products', 'origin' => 'https://example.test', 'title' => 'Products']);

    return compact('project', 'page', 'source', 'target');
}

function deliveryContext(array $fixture, array $items): TranslationContext
{
    $context = new TranslationContext(['source' => 'en', 'target' => 'fr', 'translatables' => $items], $fixture['project']);
    $context->page = $fixture['page'];
    (new ResolveLanguages)->handle($context, fn (TranslationContext $context) => $context);
    $context->needsDbLookup = collect($items);

    return $context;
}

function deliveryTranslation(array $fixture, array $attributes = []): Translation
{
    return Translation::query()->create([
        'project_id' => $fixture['project']->id, 'page_id' => $fixture['page']->id,
        'source_lang_id' => $fixture['source']->id, 'target_lang_id' => $fixture['target']->id,
        'text' => 'Welcome home', 'translated' => 'Bienvenue chez vous', 'total_words' => 2,
        'type' => TranslationType::INNER_TEXT, 'attr' => '', 'source_context' => '', 'context_hash' => '',
        'is_on' => true, 'is_reviewed' => false, 'quality' => TranslationQuality::AUTOMATIC, ...$attributes,
    ]);
}

it('withholds hidden translations instead of sending them to a provider', function () {
    $fixture = translationDeliveryFixture();
    deliveryTranslation($fixture, ['is_on' => false]);
    $context = deliveryContext($fixture, [['id' => 'hidden', 'text' => 'Welcome home', 'type' => 'text', 'attr' => '', 'context' => '']]);

    (new LookupDatabaseTranslations)->handle($context, fn (TranslationContext $context) => $context);

    expect($context->needsNmtTranslation)->toBeEmpty()
        ->and($context->translatedItems)->toBeEmpty()
        ->and($context->withheldIds->all())->toBe(['hidden']);
});

it('withholds pending automatic translations when publication requires review', function () {
    $fixture = translationDeliveryFixture(['should_display_automatics' => false]);
    deliveryTranslation($fixture);
    $context = deliveryContext($fixture, [['id' => 'pending', 'text' => 'Welcome home', 'type' => 'text', 'attr' => '', 'context' => '']]);

    (new LookupDatabaseTranslations)->handle($context, fn (TranslationContext $context) => $context);

    expect($context->needsNmtTranslation)->toBeEmpty()
        ->and($context->withheldIds->all())->toBe(['pending']);
});

it('delivers reviewed translations when publication requires review', function () {
    $fixture = translationDeliveryFixture([], ['should_display_automatics' => false]);
    deliveryTranslation($fixture, ['is_reviewed' => true]);
    $context = deliveryContext($fixture, [['id' => 'reviewed', 'text' => 'Welcome home', 'type' => 'text', 'attr' => '', 'context' => '']]);

    (new LookupDatabaseTranslations)->handle($context, fn (TranslationContext $context) => $context);

    expect($context->translatedItems->sole()->translated)->toBe('Bienvenue chez vous')
        ->and($context->needsNmtTranslation)->toBeEmpty()
        ->and($context->needsCaching)->toHaveCount(1);
});

it('keeps attribute and text cache identities separate while fanning out matching items', function () {
    config()->set('cache.default', 'array');
    Cache::store('array')->flush();
    $fixture = translationDeliveryFixture();
    $store = app(TranslationCacheStore::class);
    $hash = TextHasher::hash('Search');
    $store->set($fixture['project']->id, $fixture['page']->id, 'fr', $hash, ['translated' => 'Recherche', 'translation_id' => null, 'last_used_at' => null], 'text');
    $store->set($fixture['project']->id, $fixture['page']->id, 'fr', $hash, ['translated' => 'Rechercher', 'translation_id' => null, 'last_used_at' => null], 'attr', 'placeholder');
    $items = [['id' => 'one', 'text' => 'Search', 'type' => 'text', 'attr' => '', 'context' => ''], ['id' => 'two', 'text' => 'Search', 'type' => 'text', 'attr' => '', 'context' => ''], ['id' => 'three', 'text' => 'Search', 'type' => 'attr', 'attr' => 'placeholder', 'context' => '']];
    $context = deliveryContext($fixture, $items);

    (new CheckTranslationCache($store))->handle($context, fn (TranslationContext $context) => $context);

    expect($context->cacheHits->map(fn ($item) => [$item->id, $item->translated])->all())->toBe([['one', 'Recherche'], ['two', 'Recherche'], ['three', 'Rechercher']]);
});

it('keeps the immutable source text through repeated glossary preparation', function () {
    $fixture = translationDeliveryFixture();
    GlossaryService::store($fixture['project'], ['text' => 'Weblex', 'rule' => GlossaryRule::NEVER_TRANSLATE->value, 'is_case_sensitive' => true, 'languages' => [$fixture['target']->id]]);
    $item = ['id' => 'source', 'text' => 'Welcome to Weblex', 'type' => 'text', 'attr' => '', 'context' => ''];
    $first = deliveryContext($fixture, [$item]);
    $second = deliveryContext($fixture, [$item]);
    $first->needsNmtTranslation->push([...$item, 'original_text' => $item['text'], 'original_context' => '', 'total_words' => 3]);
    $second->needsNmtTranslation->push([...$item, 'original_text' => $item['text'], 'original_context' => '', 'total_words' => 3]);
    $pipeline = new ApplyGlossariesToText(app(GlossaryService::class));

    $pipeline->handle($first, fn (TranslationContext $context) => $context);
    $pipeline->handle($second, fn (TranslationContext $context) => $context);

    expect($first->needsNmtTranslation->sole()['original_text'])->toBe('Welcome to Weblex')
        ->and($second->needsNmtTranslation->sole()['original_text'])->toBe('Welcome to Weblex')
        ->and($first->needsNmtTranslation->sole()['provider_text'])->not->toBe('Welcome to Weblex');
});

it('persists pending automatic translations but withholds them until review', function () {
    $fixture = translationDeliveryFixture(['should_display_automatics' => false]);
    $item = ['id' => 'generated', 'text' => 'Welcome home', 'type' => 'text', 'attr' => '', 'context' => ''];
    $context = deliveryContext($fixture, [$item]);
    $context->nmtTranslated->push(new TranslatedItemDTO('generated', 'Welcome home', 'Bienvenue chez vous', 'nmt'));

    (new StoreTranslationsInDatabase)->handle($context, fn (TranslationContext $context) => $context);

    expect(Translation::query()->where('project_id', $fixture['project']->id)->sole()->is_reviewed)->toBeFalse()
        ->and($context->translatedItems)->toBeEmpty()
        ->and($context->withheldIds->all())->toBe(['generated']);
});

it('does not overwrite a manual translation when a provider result arrives late', function () {
    $fixture = translationDeliveryFixture();
    deliveryTranslation($fixture, ['translated' => 'Manual French', 'quality' => TranslationQuality::MANUAL, 'is_reviewed' => true]);
    $item = ['id' => 'late', 'text' => 'Welcome home', 'type' => 'text', 'attr' => '', 'context' => ''];
    $context = deliveryContext($fixture, [$item]);
    $context->nmtTranslated->push(new TranslatedItemDTO('late', 'Welcome home', 'Provider French', 'nmt'));

    (new StoreTranslationsInDatabase)->handle($context, fn (TranslationContext $context) => $context);

    expect(Translation::query()->where('project_id', $fixture['project']->id)->sole()->translated)->toBe('Manual French')
        ->and($context->nmtTranslated->sole()->translated)->toBe('Manual French');
});

it('continues delivery when writing the translation cache fails', function () {
    $fixture = translationDeliveryFixture();
    $context = deliveryContext($fixture, []);
    $context->needsCaching->push(['text_hash' => TextHasher::hash('Welcome home'), 'context_hash' => '', 'type' => 'text', 'attr' => '', 'translated' => 'Bienvenue chez vous', 'translation_id' => null, 'last_used_at' => null]);
    $cache = Mockery::mock(TranslationCacheStore::class);
    $cache->shouldReceive('setMany')->once()->andThrow(new RuntimeException('cache unavailable'));

    (new StoreTranslationsInCache($cache))->handle($context, fn (TranslationContext $context) => $context);

    expect($context->needsCaching)->toBeEmpty();
});
