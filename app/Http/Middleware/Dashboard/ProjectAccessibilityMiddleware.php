<?php

namespace App\Http\Middleware\Dashboard;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectAccessibilityMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $project = $request->route('project');

        if (! $project || ! $user || ! $user->can('view', $project)) {
            abort(404);
        }

        return $next($request);
    }
}
