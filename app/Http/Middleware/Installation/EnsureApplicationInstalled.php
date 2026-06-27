<?php

namespace App\Http\Middleware\Installation;

use App\Support\Installation\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app(InstallationState::class)->isInstalled() || $request->is('install', 'install/*', 'up', 'health')) {
            return $next($request);
        }

        if ($request->is('api', 'api/*')) {
            return response()->json(['message' => 'Application installation is required.'], 503);
        }

        return redirect()->route('install.show');
    }
}
