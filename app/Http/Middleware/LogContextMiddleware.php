<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::withContext([
            'ip' => $request->ip(),
            'user_id' => Auth::id(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);

        return $next($request);
    }
}
