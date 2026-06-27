<?php

namespace App\Http\Middleware\Dashboard;

use App\Enums\CollaboratorRole;
use App\Settings\GeneralSettings;
use App\Support\Installation\InstallationState;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        if (! app(InstallationState::class)->isInstalled()) {
            return parent::share($request);
        }

        $project = $request->route('project');
        $role = null;

        if ($project && $request->user()) {
            $project->loadMissing(['originalLanguage', 'acceptedOrigins', 'providerCredential']);
            $project->setRelation('firstLanguage', $project->languages()->first());
            $role = $project->user_id === $request->user()->id
                ? CollaboratorRole::OWNER->value
                : $project->collaborators()
                    ->where('users.id', $request->user()->id)
                    ->first()?->pivot->role?->value;
        }

        $settings = app(GeneralSettings::class);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'project' => $project,
            'auth' => [
                'user' => $request->user(),
                'role' => $role,
            ],
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',
            'success' => fn () => $request->session()->get('success') ?? true,
            'error' => fn () => $request->session()->get('error'),
            'message' => fn () => $request->session()->get('message') ?? '',
            'data' => fn () => $request->session()->get('data') ?? [],
            'full_url' => url()->current(),
            'marketing_url' => $settings->marketing_url,
            'dashboard_url' => $settings->dashboard_url,
            'cdn_url' => $settings->cdn_url,
        ];
    }
}
