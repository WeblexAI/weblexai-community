<?php

use App\Enums\GlossaryRule;
use App\Enums\ModelStatus;
use App\Models\Glossary;
use App\Models\Language;
use App\Models\Project;
use App\Models\User;
use App\Services\GlossaryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function glossaryServiceFixture(): array
{
    $owner = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    auth()->login($owner);
    $source = Language::query()->create([
        'name' => 'English',
        'country_name' => 'United States',
        'iso_2' => 'en',
        'iso_3' => 'eng',
        'is_active' => ModelStatus::ACTIVE,
    ]);
    $french = Language::query()->create([
        'name' => 'French',
        'country_name' => 'France',
        'iso_2' => 'fr',
        'iso_3' => 'fra',
        'is_active' => ModelStatus::ACTIVE,
    ]);
    $spanish = Language::query()->create([
        'name' => 'Spanish',
        'country_name' => 'Spain',
        'iso_2' => 'es',
        'iso_3' => 'spa',
        'is_active' => ModelStatus::ACTIVE,
    ]);
    $projectId = DB::table('projects')->insertGetId([
        'uuid' => Str::uuid()->toString(),
        'user_id' => $owner->id,
        'name' => 'Glossary project',
        'slug' => 'glossary-project',
        'api_key_hash' => hash('sha256', 'glossary-api-key'),
        'original_language_id' => $source->id,
        'is_active' => ModelStatus::ACTIVE->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $project = Project::findOrFail($projectId);

    return compact('project', 'source', 'french', 'spanish');
}

it('loads active glossaries that apply to the requested language', function () {
    $fixture = glossaryServiceFixture();
    $project = $fixture['project'];
    $french = $fixture['french'];
    $spanish = $fixture['spanish'];

    $allLanguages = Glossary::query()->create([
        'project_id' => $project->id,
        'text' => 'Weblex',
        'translated' => 'Weblex',
        'placeholder' => 'GLS_ALL',
        'is_all_languages' => true,
        'rule' => GlossaryRule::NEVER_TRANSLATE,
        'is_active' => ModelStatus::ACTIVE,
    ]);
    $frenchOnly = Glossary::query()->create([
        'project_id' => $project->id,
        'text' => 'dashboard',
        'translated' => 'tableau de bord',
        'placeholder' => 'GLS_FR',
        'is_all_languages' => false,
        'rule' => GlossaryRule::ALWAYS_TRANSLATE,
        'is_active' => ModelStatus::ACTIVE,
    ]);
    $frenchOnly->languages()->attach($french->id);
    $spanishOnly = Glossary::query()->create([
        'project_id' => $project->id,
        'text' => 'button',
        'translated' => 'boton',
        'placeholder' => 'GLS_ES',
        'is_all_languages' => false,
        'rule' => GlossaryRule::ALWAYS_TRANSLATE,
        'is_active' => ModelStatus::ACTIVE,
    ]);
    $spanishOnly->languages()->attach($spanish->id);
    Glossary::query()->create([
        'project_id' => $project->id,
        'text' => 'inactive',
        'translated' => 'inactive',
        'placeholder' => 'GLS_OFF',
        'is_all_languages' => true,
        'rule' => GlossaryRule::NEVER_TRANSLATE,
        'is_active' => ModelStatus::INACTIVE,
    ]);

    $glossaries = app(GlossaryService::class)->getProjectGlossaries($project, $french);

    expect($glossaries->pluck('id')->all())->toContain($allLanguages->id, $frenchOnly->id)
        ->and($glossaries->pluck('id')->all())->not->toContain($spanishOnly->id);
});

it('protects glossary terms with placeholders and restores provider output', function () {
    $glossaries = collect([
        new Glossary([
            'text' => 'Weblex',
            'translated' => 'Weblex',
            'placeholder' => 'GLS_BRAND',
            'is_case_sensitive' => true,
            'rule' => GlossaryRule::NEVER_TRANSLATE,
        ]),
        new Glossary([
            'text' => 'dashboard',
            'translated' => 'tableau de bord',
            'placeholder' => 'GLS_DASHBOARD',
            'is_case_sensitive' => false,
            'rule' => GlossaryRule::ALWAYS_TRANSLATE,
        ]),
    ]);
    $service = app(GlossaryService::class);

    $result = $service->applyToText('Open the Weblex Dashboard now.', $glossaries);

    expect($result['text'])->toBe('Open the GLS_BRAND GLS_DASHBOARD now.')
        ->and($result['applied_glossaries'])->toBe([
            'GLS_BRAND' => 'Weblex',
            'GLS_DASHBOARD' => 'tableau de bord',
        ])
        ->and($service->replacePlaceholders('Ouvrez GLS_BRAND GLS_DASHBOARD maintenant.', $result['applied_glossaries']))
        ->toBe('Ouvrez Weblex tableau de bord maintenant.');
});

it('does not replace glossary matches inside larger words', function () {
    $glossaries = collect([
        new Glossary([
            'text' => 'app',
            'translated' => 'application',
            'placeholder' => 'GLS_APP',
            'is_case_sensitive' => false,
            'rule' => GlossaryRule::ALWAYS_TRANSLATE,
        ]),
    ]);

    $result = app(GlossaryService::class)->applyToText('Use the app, not pineapple.', $glossaries);

    expect($result['text'])->toBe('Use the GLS_APP, not pineapple.')
        ->and($result['applied_glossaries'])->toBe(['GLS_APP' => 'application']);
});
