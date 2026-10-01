<?php

use App\Enums\GlossaryRule;
use App\Enums\TranslationQuality;
use App\Exports\TranslationExport;
use App\Imports\TranslationImport;
use App\Models\Language;
use App\Models\Page;
use App\Models\Project;
use App\Models\Translation;
use App\Models\User;
use App\Services\GlossaryService;
use App\Services\PageService;
use App\Support\TextHasher;
use Maatwebsite\Excel\Facades\Excel;

function translationManagementFixture(): array
{
    $owner = User::factory()->create();
    auth()->login($owner);
    $source = Language::query()->firstOrCreate(['iso_2' => 'en'], ['name' => 'English', 'country_name' => 'United States', 'iso_3' => 'eng']);
    $target = Language::query()->firstOrCreate(['iso_2' => 'fr'], ['name' => 'French', 'country_name' => 'France', 'iso_3' => 'fra']);
    $project = Project::query()->create(['name' => 'Project '.uniqid(), 'user_id' => $owner->id, 'original_language_id' => $source->id]);
    $project->languages()->attach($target, ['is_public' => true, 'should_display_automatics' => true]);
    $page = Page::query()->create(['project_id' => $project->id, 'domain' => 'example.com/', 'origin' => 'https://example.com/', 'title' => 'Home']);
    $translation = Translation::query()->create([
        'project_id' => $project->id, 'page_id' => $page->id,
        'source_lang_id' => $source->id, 'target_lang_id' => $target->id,
        'text' => 'Open Weblex now.', 'translated' => 'Ouvrez Weblex.',
        'total_words' => 3, 'is_reviewed' => true, 'quality' => TranslationQuality::MANUAL,
    ]);

    return compact('owner', 'source', 'target', 'project', 'page', 'translation');
}

it('rejects translation mutations against another project', function (string $route, array $payload) {
    $own = translationManagementFixture();
    $other = translationManagementFixture();
    $this->actingAs($own['owner'])->putJson(route('projects.translations.'.$route, $own['project']->slug), [
        'translation_id' => $other['translation']->id, ...$payload,
    ])->assertUnprocessable()->assertJsonValidationErrors('translation_id');
    expect($other['translation']->fresh()->translated)->toBe('Ouvrez Weblex.')
        ->and($other['translation']->fresh()->is_on)->toBeTrue()
        ->and($other['translation']->fresh()->is_reviewed)->toBeTrue();
})->with([
    'translated' => ['translated', ['translated' => 'Changed']],
    'review' => ['review', ['is_reviewed' => false]],
    'visibility' => ['visibility', ['is_on' => false]],
]);

it('requires a new review after editing and advances the delivery revision', function () {
    $fixture = translationManagementFixture();
    $revision = $fixture['project']->fresh()->delivery_revision;
    $this->actingAs($fixture['owner'])->putJson(route('projects.translations.translated', $fixture['project']->slug), [
        'translation_id' => $fixture['translation']->id, 'translated' => 'Ouvrez maintenant.',
    ])->assertRedirect();
    expect($fixture['translation']->fresh()->is_reviewed)->toBeFalse()
        ->and($fixture['translation']->fresh()->quality)->toBe(TranslationQuality::MANUAL)
        ->and($fixture['project']->fresh()->delivery_revision)->toBeGreaterThan($revision);
});

it('retains hidden manual content while scheduling glossary regeneration', function () {
    $fixture = translationManagementFixture();
    $translation = $fixture['translation'];
    $translation->update(['is_on' => false]);
    $glossary = GlossaryService::store($fixture['project'], [
        'text' => 'Weblex', 'rule' => GlossaryRule::NEVER_TRANSLATE->value,
        'is_case_sensitive' => true, 'languages' => [$fixture['target']->id],
    ]);
    expect($translation->fresh()->needs_regeneration)->toBeTrue()
        ->and($translation->fresh()->is_on)->toBeFalse()
        ->and($translation->fresh()->translated)->toBe('Ouvrez Weblex.');
    $translation->updateQuietly(['needs_regeneration' => false]);
    GlossaryService::delete($fixture['project'], $glossary);
    expect($translation->fresh()->needs_regeneration)->toBeTrue();
});

it('regenerates both old and new glossary language scopes and removes detached languages', function () {
    $fixture = translationManagementFixture();
    $spanish = Language::query()->create(['name' => 'Spanish', 'country_name' => 'Spain', 'iso_2' => 'es', 'iso_3' => 'spa']);
    $other = $fixture['translation']->replicate(['uuid']);
    $other->target_lang_id = $spanish->id;
    $other->save();
    $glossary = GlossaryService::store($fixture['project'], [
        'text' => 'Weblex', 'rule' => GlossaryRule::NEVER_TRANSLATE->value,
        'is_case_sensitive' => true, 'languages' => [$fixture['target']->id],
    ]);
    Translation::query()->where('project_id', $fixture['project']->id)->update(['needs_regeneration' => false]);
    GlossaryService::update($fixture['project'], $glossary, [
        'rule' => GlossaryRule::NEVER_TRANSLATE->value, 'is_case_sensitive' => true, 'languages' => [$spanish->id],
    ]);
    expect($fixture['translation']->fresh()->needs_regeneration)->toBeTrue()
        ->and($other->fresh()->needs_regeneration)->toBeTrue()
        ->and($glossary->languages()->pluck('languages.id')->all())->toBe([$spanish->id]);
});

it('blacklists a page without deleting human translations', function () {
    $fixture = translationManagementFixture();
    PageService::toggleBlacklist($fixture['page']);
    expect($fixture['page']->fresh()->is_blacklisted)->toBeTrue()
        ->and($fixture['translation']->fresh()->translated)->toBe('Ouvrez Weblex.');
});

it('round trips typed translations through the exported spreadsheet', function () {
    $fixture = translationManagementFixture();
    $fixture['translation']->updateQuietly(['type' => 'attr', 'attr' => 'title', 'source_context' => 'Navigation', 'context_hash' => TextHasher::hash('Navigation')]);
    $export = new TranslationExport($fixture['project'], $fixture['page'], $fixture['target']);
    $path = tempnam(sys_get_temp_dir(), 'weblex-import-').'.xlsx';
    try {
        file_put_contents($path, Excel::raw($export, Maatwebsite\Excel\Excel::XLSX));
        Excel::import(new TranslationImport($fixture['owner'], $fixture['page'], $fixture['source'], $fixture['target']), $path);
        expect(Translation::query()->count())->toBe(1)
            ->and($fixture['translation']->fresh()->attr)->toBe('title')
            ->and($fixture['translation']->fresh()->source_context)->toBe('Navigation')
            ->and($fixture['translation']->fresh()->translated)->toBe('Ouvrez Weblex.');
    } finally {
        unlink($path);
    }
});
