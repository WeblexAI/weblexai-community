<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\CollaboratorRole;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\ProviderCredentials\ProviderCredentialResource;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class ProjectSetupController extends Controller
{
    public function index(Project $project): Response
    {
        if (! CollaboratorRole::isAuthorized($project, auth()->user(), CollaboratorRole::canManageSettings())) {
            abort(403);
        }

        return Inertia::render('Project/Setup', [
            'apiKey' => $project->api_key,
            'adminUrls' => [
                'providerCredentials' => ProviderCredentialResource::getUrl('index'),
                'project' => ProjectResource::getUrl('view', ['record' => $project]),
                'projectEdit' => ProjectResource::getUrl('edit', ['record' => $project]),
            ],
        ]);
    }
}
