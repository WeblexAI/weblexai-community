<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ExcludedBlock\BulkDeleteExcludedBlockRequest;
use App\Http\Requests\Dashboard\ExcludedBlock\CreateExcludedBlockRequest;
use App\Http\Requests\Dashboard\ExcludedBlock\UpdateExcludedBlockRequest;
use App\Models\ExcludedBlock;
use App\Models\Project;
use App\Services\Cache\ProjectCacheInvalidationService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExcludedBlockController extends Controller
{
    public function index(Project $project): Response
    {
        $excludedBlock = QueryBuilder::for(ExcludedBlock::class)
            ->allowedFilters([
                AllowedFilter::callback('q', function (Builder $query, $input) {
                    if (is_array($input)) {
                        $input = implode(',', $input);
                    }
                    $query->where('selector', 'LIKE', "%$input%")
                        ->orWhere('description', 'LIKE', "%$input%");
                }),
            ])
            ->where('project_id', $project->id)
            ->paginate(10)->appends(request()->query());

        return Inertia::render('Project/ExcludedBlocks', [
            'excludedBlocks' => $excludedBlock,
        ]);
    }

    public function create(Project $project, CreateExcludedBlockRequest $request): RedirectResponse
    {
        $this->authorize('create', [ExcludedBlock::class, $project]);

        DB::beginTransaction();
        try {
            $project->excludedBlocks()->create($request->validated());
            DB::commit();

            return response()->success('Block saved successfully.');
        } catch (Exception $exception) {
            DB::rollBack();

            return response()->error();
        }
    }

    public function update(Project $project, ExcludedBlock $block, UpdateExcludedBlockRequest $request): RedirectResponse
    {
        $this->authorize('update', $block);

        DB::beginTransaction();
        try {
            $block->update($request->validated());
            DB::commit();

            return response()->success();
        } catch (Exception $exception) {
            DB::rollBack();

            return response()->error();
        }
    }

    public function delete(Project $project, ExcludedBlock $block): RedirectResponse
    {
        $this->authorize('delete', $block);

        DB::beginTransaction();
        try {
            $block->delete();
            DB::commit();

            return response()->success('Block deleted.');
        } catch (Exception $exception) {
            DB::rollBack();

            return response()->error();
        }
    }

    public function bulkDelete(Project $project, BulkDeleteExcludedBlockRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        $validated = $request->validated();
        try {
            $project->excludedBlocks()
                ->whereIn('id', $validated['block_ids'])
                ->delete();
            DB::commit();
            app(ProjectCacheInvalidationService::class)->clearProjectConfig($project->id);

            return response()->success(count($validated['block_ids']).' blocks deleted.');
        } catch (Exception $exception) {
            DB::rollBack();

            return response()->error();
        }
    }
}
