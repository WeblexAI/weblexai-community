<?php

namespace App\Providers;

use App\Enums\ModelStatus;
use App\Models\User;
use App\Models\View;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \CyrildeWit\EloquentViewable\Contracts\View::class,
            View::class
        );
    }

    public function boot(): void
    {
        Model::unguard();
        Model::automaticallyEagerLoadRelationships();

        Gate::define('viewLogViewer', function (?User $user = null): bool {
            $user ??= auth()->user();

            return $user?->is_active === ModelStatus::ACTIVE && $user->hasRole('admin');
        });
        Gate::define('downloadLogFile', function (?User $user = null): bool {
            $user ??= auth()->user();

            return $user !== null && Gate::forUser($user)->allows('viewLogViewer');
        });
        Gate::define('downloadLogFolder', function (?User $user = null): bool {
            $user ??= auth()->user();

            return $user !== null && Gate::forUser($user)->allows('viewLogViewer');
        });
        Gate::define('deleteLogFile', fn (): bool => false);
        Gate::define('deleteLogFolder', fn (): bool => false);

        RateLimiter::for('translation-api', function (Request $request): Limit {
            $identity = $request->bearerToken()
                ? hash('sha256', $request->bearerToken())
                : $request->ip();

            return Limit::perMinute((int) config('app.translation_api_rate_limit', 120))
                ->by($identity);
        });
    }
}
