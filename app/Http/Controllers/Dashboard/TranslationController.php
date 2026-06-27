<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Http\Requests\Translation\UpdateReviewRequest;
use App\Http\Requests\Translation\UpdateTranslatedRequest;
use App\Http\Requests\Translation\UpdateVisibilityRequest;
use App\Models\Project;
use App\Models\Translation;
use Illuminate\Support\Facades\Log;

class TranslationController extends Controller
{
    public function updateTranslation(Project $project, UpdateTranslatedRequest $request)
    {
        try {
            $validated = $request->validated();
            $translation = Translation::query()
                ->find($validated['translation_id']);
            $translation->update([
                'translated' => $validated['translated'],
            ]);

            return response()->success('Translation saved');
        } catch (\Exception $exception) {
            Log::error($exception);
            return response()->error();
        }
    }

    public function updateReview(Project $project, UpdateReviewRequest $request)
    {
        try {
            $validated = $request->validated();
            $translation = Translation::query()
                ->find($validated['translation_id']);
            $translation->update([
                'is_reviewed' => $validated['is_reviewed'],
            ]);
            $message = $translation->is_reviewed ? 'Translation marked as reviewed' : 'Translation marked as pending review';

            return response()->success($message);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response()->error();
        }
    }

    public function updateVisibility(Project $project, UpdateVisibilityRequest $request)
    {
        try {
            $validated = $request->validated();
            $translation = Translation::query()
                ->find($validated['translation_id']);
            $translation->update([
                'is_on' => $validated['is_on'],
            ]);
            
            $message = $validated['is_on'] ? 'Translation turned on' : 'Translation turned off';
            return response()->success($message);
        } catch (\Exception $exception) {
            return response()->error();
        }
    }
}

