<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Http\Requests\Glossary\StoreGlossaryRequest;
use App\Http\Requests\Glossary\UpdateGlossaryRequest;
use App\Models\Glossary;
use App\Models\Project;
use App\Services\GlossaryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class GlossaryController extends Controller
{
    public function index(Project $project): Response
    {
        $languages = $project->languages;

        $glossaries = QueryBuilder::for(Glossary::class)
            ->allowedFilters([
                AllowedFilter::callback('q', function (Builder $query, $input) {
                    // in case q contains "," , spatie query builder treats it as array
                    if (is_array($input)) {
                        $input = implode(',', $input);
                    }
                    $query->where('text', 'LIKE', "%$input%");
                }),
                AllowedFilter::callback('language', function (Builder $query, $input) {
                    if ($input === 'all') {
                        return;
                    }
                    $query
                        ->where('is_all_languages', true)
                        ->orWhereRelation('languages', 'iso_2', $input);
                }),
            ])
            ->where('project_id', $project->id)
            ->with(['languages:id,name'])
            ->paginate()->appends(request()->query());

        return Inertia::render('Project/Glossaries', [
            'languages' => $languages,
            'glossaries' => $glossaries,
        ]);
    }

    public function store(Project $project, StoreGlossaryRequest $request): RedirectResponse
    {
        $this->authorize('manage', [Glossary::class, $project]);

        DB::beginTransaction();
        try {
            $glossary = GlossaryService::store($project, $request->validated());
            DB::commit();

            return response()->success('Glossary added successfully, translations matching this glossary will be deleted and regenerated on your next visit.');
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);

            return response()->error('An error occurred while adding glossary.');
        }
    }

    public function update(Project $project, Glossary $glossary, UpdateGlossaryRequest $request): RedirectResponse
    {
        $this->authorize('manage', [Glossary::class, $project]);

        DB::beginTransaction();
        try {
            GlossaryService::update($project, $glossary, $request->validated());
            DB::commit();

            return response()->success('Glossary updated.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return response()->error('An error occurred while updating glossary.');
        }
    }

    public function delete(Project $project, Glossary $glossary): RedirectResponse
    {
        $this->authorize('delete', $glossary);

        DB::beginTransaction();
        try {
            GlossaryService::delete($project, $glossary);
            DB::commit();

            return response()->success('Glossary removed.');
        } catch (\Exception $exception) {
            Log::error($exception);
            DB::rollBack();

            return response()->error('An error occurred while removing glossary.');
        }
    }

    public function bulkDelete(Project $project, Request $request): RedirectResponse
    {
        $this->authorize('manage', [Glossary::class, $project]);

        DB::beginTransaction();
        try {
            $glossaryIds = $request->glossaries ?? [];
            GlossaryService::deleteBulk($project, $glossaryIds);
            DB::commit();

            return response()->success('Glossaries removed.');
        } catch (\Exception $exception) {
            DB::rollBack();

            return response()->error('An error occurred while removing glossaries.');
        }
    }
}

