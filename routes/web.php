<?php

use App\Http\Controllers\Dashboard\ActivityLogController;
use App\Http\Controllers\Dashboard\ExcludedBlockController;
use App\Http\Controllers\Dashboard\GlossaryController;
use App\Http\Controllers\Installation\InstallController;
use App\Http\Controllers\Dashboard\LanguageSwitcherController;
use App\Http\Controllers\Dashboard\OverviewController;
use App\Http\Controllers\Dashboard\PageController;
use App\Http\Controllers\Dashboard\PageViewController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\ProjectController;
use App\Http\Controllers\Dashboard\ProjectLanguageController;
use App\Http\Controllers\Dashboard\ProjectOverviewController;
use App\Http\Controllers\Dashboard\ProjectSetupController;
use App\Http\Controllers\Dashboard\TranslationController;
use App\Http\Controllers\Dashboard\TranslationImportExportController;
use App\Http\Controllers\Dashboard\TranslationModelController;
use App\Http\Controllers\Dashboard\TranslationRequestController;
use App\Http\Controllers\Dashboard\TranslationUsageController;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallController::class, 'show'])->name('install.show');
Route::post('/install', [InstallController::class, 'store'])->name('install.store');

Route::redirect('/', '/login')->name('home');
Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::middleware('auth')->group(function () {
    Route::get('admin/logs', fn () => redirect('/log-viewer'))->name('admin.logs');

    Route::get('overview', [OverviewController::class, 'index'])->name('overview');
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');

    Route::get('profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('profile', [ProfileController::class, 'update']);
    Route::put('profile/change-password', [ProfileController::class, 'changePassword'])
        ->name('profile.password');

    Route::prefix('projects/{project:slug}')
        ->as('projects.')
        ->middleware('project-access')
        ->group(function () {
            Route::get('overview', [ProjectOverviewController::class, 'overview'])->name('overview');
            Route::get('overview/members', [ProjectOverviewController::class, 'getCollaborators'])
                ->name('overview.members');
            Route::get('overview/project-details', [ProjectOverviewController::class, 'getProjectDetails'])
                ->name('overview.project-details');
            Route::get('overview/activities', [ProjectOverviewController::class, 'getActivityLogs'])
                ->name('overview.activities');
            Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs');

            Route::put('translations/translated', [TranslationController::class, 'updateTranslation'])
                ->name('translations.translated');
            Route::put('translations/review', [TranslationController::class, 'updateReview'])
                ->name('translations.review');
            Route::put('translations/visibility', [TranslationController::class, 'updateVisibility'])
                ->name('translations.visibility');
            Route::post('translations/export', [TranslationImportExportController::class, 'export'])
                ->name('translations.export');
            Route::post('translations/import', [TranslationImportExportController::class, 'import'])
                ->name('translations.import');
            Route::get('translations/import/sample', [TranslationImportExportController::class, 'downloadImportSample'])
                ->name('translations.import.sample');

            Route::get('settings', [ProjectController::class, 'settings'])->name('settings');
            Route::patch('settings', [ProjectController::class, 'update'])->name('update');

            Route::prefix('languages')->as('languages.')->group(function () {
                Route::get('/', [ProjectLanguageController::class, 'index'])->name('index');
                Route::post('/', [ProjectLanguageController::class, 'attachLanguage']);
                Route::get('{language:iso_2}', [ProjectLanguageController::class, 'show'])
                    ->scopeBindings()
                    ->name('show');
                Route::delete('{language}', [ProjectLanguageController::class, 'detachLanguage'])
                    ->scopeBindings()
                    ->name('delete');
                Route::post('{language}/publicity', [ProjectLanguageController::class, 'togglePublicity'])
                    ->scopeBindings()
                    ->name('toggle-publicity');
                Route::post('{language}/automatics', [ProjectLanguageController::class, 'toggleAutomatics'])
                    ->scopeBindings()
                    ->name('toggle-automatics');
                Route::post('{language}/enable', [ProjectLanguageController::class, 'enableLanguage'])
                    ->scopeBindings()
                    ->name('enable');
                Route::post('{language}/disable', [ProjectLanguageController::class, 'disableLanguage'])
                    ->scopeBindings()
                    ->name('disable');
                Route::get('{language:iso_2}/pages', [PageController::class, 'index'])
                    ->name('pages.index');
            });

            Route::post('pages/{page}/blacklist', [PageController::class, 'toggleBlacklist'])
                ->name('pages.blacklist');
            Route::post('pages/bulk-blacklist', [PageController::class, 'toggleBulkBlacklist'])
                ->name('pages.blacklist.bulk');

            Route::prefix('glossaries')->as('glossaries.')->group(function () {
                Route::get('/', [GlossaryController::class, 'index'])->name('index');
                Route::post('/', [GlossaryController::class, 'store'])->name('create');
                Route::put('{glossary}', [GlossaryController::class, 'update'])->name('update');
                Route::delete('{glossary}', [GlossaryController::class, 'delete'])->name('delete');
                Route::delete('bulk/delete', [GlossaryController::class, 'bulkDelete'])->name('bulk-delete');
            });

            Route::get('translation-requests', [TranslationRequestController::class, 'index'])
                ->name('translation-requests.index');
            Route::get('translation-usage', [TranslationUsageController::class, 'index'])
                ->name('translation-usage.index');
            Route::get('page-views', [PageViewController::class, 'index'])->name('page-views.index');

            Route::get('translation-model', [TranslationModelController::class, 'index'])
                ->name('translation-model.index');
            Route::put('translation-model', [TranslationModelController::class, 'update'])
                ->name('translation-model.update');

            Route::get('language-switcher', [LanguageSwitcherController::class, 'index'])
                ->name('language-switcher.index');
            Route::put('language-switcher', [LanguageSwitcherController::class, 'update'])
                ->name('language-switcher.update');

            Route::prefix('excluded-blocks')->as('excluded-blocks.')->group(function () {
                Route::get('/', [ExcludedBlockController::class, 'index'])->name('index');
                Route::post('/', [ExcludedBlockController::class, 'create'])->name('create');
                Route::put('{block}', [ExcludedBlockController::class, 'update'])->name('update');
                Route::delete('{block}', [ExcludedBlockController::class, 'delete'])->name('delete');
                Route::delete('bulk/delete', [ExcludedBlockController::class, 'bulkDelete'])
                    ->name('delete.bulk');
            });

            Route::get('setup', [ProjectSetupController::class, 'index'])->name('setup');
        });
});

require __DIR__.'/auth.php';
