<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Enums\TranslationModelType;
use App\Http\Requests\Dashboard\UpdateTranslationModelRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TranslationModelController extends Controller
{
    public function index(Project $project): Response
    {
        $this->authorize('update', $project);

        return Inertia::render('Project/TranslationModel');
    }

    public function update(Project $project, UpdateTranslationModelRequest $request): RedirectResponse
    {
        $modelType = $project->providerCredential?->provider->type();
        $project->update([
            'website_description' => $modelType === TranslationModelType::LLM
                ? $request->validated('website_description')
                : null,
            'translation_tone' => $modelType === TranslationModelType::LLM
                ? $request->validated('translation_tone')
                : null,
            'translation_audience' => $modelType === TranslationModelType::LLM
                ? $request->validated('translation_audience')
                : null,
        ]);

        return response()->success('Translation model updated.');
    }
}

