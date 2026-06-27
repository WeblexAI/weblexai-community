<?php

namespace App\Http\Middleware\Dashboard;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->force_password_change || $user->hasRole('admin')) {
            return $next($request);
        }

        if ($request->routeIs('profile', 'profile.password', 'logout')) {
            return $next($request);
        }

        return redirect()->route('profile')
            ->with('message', 'Change your temporary password before continuing.');
    }
}
