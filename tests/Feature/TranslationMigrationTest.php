<?php

use App\Models\Language;
use App\Models\Page;
use App\Models\Project;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('merges duplicate pages and retains the latest manual translation during upgrade', function () {
    $owner = User::factory()->create();
    auth()->login($owner);
    $source = Language::query()->create(['name' => 'English', 'country_name' => 'United States', 'iso_2' => 'en', 'iso_3' => 'eng']);
    $target = Language::query()->create(['name' => 'French', 'country_name' => 'France', 'iso_2' => 'fr', 'iso_3' => 'fra']);
    $project = Project::query()->create(['name' => 'Migration project', 'original_language_id' => $source->id]);
    $page = Page::query()->create(['project_id' => $project->id, 'domain' => 'example.com/', 'origin' => 'https://example.com/']);
    $translation = Translation::query()->create([
        'project_id' => $project->id, 'page_id' => $page->id, 'source_lang_id' => $source->id, 'target_lang_id' => $target->id,
        'text' => 'Hello world', 'translated' => 'Old manual', 'total_words' => 2, 'quality' => 'manual', 'updated_at' => '2026-01-01 00:00:00',
    ]);
    $migration = require database_path('migrations/2026_09_30_215320_add_translation_identity_and_revisions.php');
    $migration->down();
    $duplicatePage = DB::table('pages')->insertGetId([
        'uuid' => Str::uuid()->toString(), 'project_id' => $project->id, 'domain' => $page->domain,
        'origin' => $page->origin, 'is_blacklisted' => true,
    ]);
    $row = DB::table('translations')->where('id', $translation->id)->first();
    $values = (array) $row;
    unset($values['id']);
    $values['page_id'] = $duplicatePage;
    $values['text_hash'] = str_repeat('f', 32);
    $values['type'] = null;
    $values['attr'] = null;
    $values['translated'] = 'Latest human edit';
    $values['updated_at'] = '2026-02-01 00:00:00';
    $winner = DB::table('translations')->insertGetId($values);
    $values['quality'] = 'automatic';
    $values['is_reviewed'] = true;
    $values['translated'] = 'Newest automatic';
    $values['updated_at'] = '2026-03-01 00:00:00';
    DB::table('translations')->insert($values);
    $requestId = DB::table('translation_requests')->insertGetId([
        'project_id' => $project->id, 'page_id' => $duplicatePage, 'source_lang_id' => $source->id, 'target_lang_id' => $target->id,
        'ip' => '127.0.0.1', 'uuid' => Str::uuid()->toString(),
    ]);
    $migration->up();
    expect(DB::table('pages')->where('project_id', $project->id)->count())->toBe(1)
        ->and(Page::findOrFail($page->id)->is_blacklisted)->toBeTrue()
        ->and(DB::table('translation_requests')->where('id', $requestId)->value('page_id'))->toBe($page->id)
        ->and(DB::table('translations')->count())->toBe(1)
        ->and(Translation::findOrFail($winner)->translated)->toBe('Latest human edit')
        ->and(Translation::findOrFail($winner)->page_id)->toBe($page->id);
});
