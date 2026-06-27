<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Exports\TranslationExport;
use App\Http\Requests\Translation\ExportRequest;
use App\Http\Requests\Translation\ImportRequest;
use App\Imports\TranslationImport;
use App\Models\Project;
use App\Models\Translation;
use App\Services\Cache\TranslationCacheInvalidationService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TranslationImportExportController extends Controller
{
    public function export(ExportRequest $request, Project $project): BinaryFileResponse
    {
        $this->authorize('export', [Translation::class, $project]);
        $page = $project->pages()->findOrFail($request->validated('page_id'));
        $targetLanguage = $project->languages()->findOrFail($request->validated('target_lang_id'));

        return Excel::download(
            new TranslationExport($project, $page, $targetLanguage),
            "{$project->slug}-{$targetLanguage->iso_2}-translations.xlsx",
        );
    }

    public function import(ImportRequest $request, Project $project)
    {
        $this->authorize('import', [Translation::class, $project]);
        $page = $project->pages()->findOrFail($request->validated('page_id'));
        $targetLanguage = $project->languages()->findOrFail($request->validated('target_lang_id'));

        try {
            Excel::import(
                new TranslationImport(auth()->user(), $page, $project->originalLanguage, $targetLanguage),
                $request->file('file'),
            );
            app(TranslationCacheInvalidationService::class)
                ->forgetPageLang($project->id, $page->id, $targetLanguage->iso_2);

            return response()->success('Import completed.');
        } catch (\Throwable $exception) {
            Log::error($exception);

            return response()->error('Import failed. Check the spreadsheet structure.');
        }
    }

    public function downloadImportSample(Project $project): BinaryFileResponse
    {
        return response()->download(storage_path('app/private/imports/translation-import-sample.xlsx'));
    }
}

