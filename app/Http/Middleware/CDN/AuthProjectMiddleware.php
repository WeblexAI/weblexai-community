<?php

namespace App\Http\Middleware\CDN;

use App\Enums\ModelStatus;
use App\Models\Project;
use App\Support\OriginNormalizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthProjectMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->bearerToken();
        $origin = OriginNormalizer::fromUrl($request->header('Origin'));

        if (! $apiKey || ! $origin) {
            return $this->unauthorized();
        }

        $project = Project::query()
            ->where('api_key_hash', hash('sha256', $apiKey))
            ->whereHas(
                'acceptedOrigins',
                fn ($query) => $query->where('normalized_origin', $origin),
            )
            ->with([
                'owner',
                'acceptedOrigins',
                'languageSwitcherConfig',
                'originalLanguage',
                'languages',
                'excludedBlocks:project_id,selector',
            ])
            ->first();

        if (! $project
            || $project->is_active === ModelStatus::INACTIVE
            || $project->owner?->is_active === ModelStatus::INACTIVE) {
            return $this->unauthorized();
        }

        $pageUrl = urldecode((string) $request->header('X-Page-Url'));

        if (OriginNormalizer::fromUrl($pageUrl) !== $origin) {
            return $this->unauthorized();
        }

        $request->attributes->set('project', $project);
        $request->attributes->set('requestOrigin', $origin);
        $request->attributes->set('pageUrl', $pageUrl);
        $request->attributes->set(
            'pageTitle',
            trim(urldecode((string) $request->header('X-Page-Title'))),
        );

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
    }
}
