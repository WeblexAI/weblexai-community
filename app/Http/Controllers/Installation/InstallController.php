<?php

namespace App\Http\Controllers\Installation;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Installation\InstallApplicationRequest;
use App\Models\User;
use App\Support\Installation\EnvironmentFileWriter;
use App\Support\Installation\InstallationState;
use App\Support\Installation\RequirementsChecker;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class InstallController extends Controller
{
    public function show(Request $request, InstallationState $state, RequirementsChecker $checker): View|RedirectResponse
    {
        if ($state->isInstalled()) {
            return redirect('/admin');
        }

        return view('install.index', [
            'checks' => $checker->system(),
            'currentRequestUrl' => rtrim($request->getSchemeAndHttpHost(), '/'),
            'defaultAppUrl' => $this->suggestedApplicationUrl($request),
            'isDocker' => config('community.deployment_mode') === 'docker',
            'progress' => $state->progress(),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function store(
        InstallApplicationRequest $request,
        InstallationState $state,
        RequirementsChecker $checker,
        EnvironmentFileWriter $environment,
    ): RedirectResponse {
        if ($state->isInstalled()) {
            return redirect()->route('login');
        }

        try {
            return $state->exclusively(function () use ($request, $state, $checker, $environment) {
                if ($state->isInstalled()) {
                    return redirect('/admin');
                }

                $input = $request->validated();
                $checks = [...$checker->system(), ...$checker->infrastructure($input)];
                $failed = collect($checks)->where('passed', false);

                if ($failed->isNotEmpty()) {
                    $infrastructureErrors = $failed
                        ->map(fn (array $check): string => "{$check['name']}: {$check['detail']}")
                        ->values()
                        ->all();

                    return back()
                        ->withInput($request->except(['admin_password', 'admin_password_confirmation']))
                        ->withErrors(['infrastructure' => $infrastructureErrors]);
                }

                $state->saveProgress('writing_environment');
                $appKey = $environment->write($this->environmentValues($input));
                $this->applyRuntimeConfiguration($input, $appKey);

                $state->saveProgress('migrating');
                Artisan::call('migrate', ['--force' => true]);
                Artisan::call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

                $state->saveProgress('creating_admin');
                $admin = DB::transaction(function () use ($input) {
                    $admin = User::query()->firstOrCreate(
                        ['email' => $input['admin_email']],
                        [
                            'name' => $input['admin_name'],
                            'password' => $input['admin_password'],
                            'force_password_change' => false,
                        ],
                    );

                    $admin->assignRole(UserRole::ADMIN->value);

                    return $admin;
                });

                Artisan::call('storage:link');
                $state->complete();
                Auth::login($admin);

                return redirect('/admin')
                    ->with('success', 'WeblexAI Community Edition is ready.');
            });
        } catch (Throwable $exception) {
            Log::error('Installation failed.', ['exception' => $exception]);

            return back()
                ->withInput($request->except(['admin_password', 'admin_password_confirmation']))
                ->withErrors(['installation' => 'Installation could not be completed. Review storage/logs/laravel.log, correct the issue, and retry.']);
        }
    }

    private function environmentValues(array $input): array
    {
        return [
            'APP_NAME' => $input['app_name'],
            'APP_ENV' => 'production',
            'APP_DEBUG' => false,
            'APP_URL' => rtrim($input['app_url'], '/'),
            'APP_LOCALE' => $input['app_locale'],
            'APP_TIMEZONE' => $input['app_timezone'],
            'APP_INSTALLED' => false,
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => $input['db_host'],
            'DB_PORT' => $input['db_port'],
            'DB_DATABASE' => $input['db_database'],
            'DB_USERNAME' => $input['db_username'],
            'DB_PASSWORD' => $input['db_password'] ?? '',
            'REDIS_CLIENT' => 'phpredis',
            'REDIS_HOST' => $input['redis_host'],
            'REDIS_PORT' => $input['redis_port'],
            'REDIS_PASSWORD' => $input['redis_password'] ?? null,
            'REDIS_DB' => $input['redis_db'],
            'REDIS_CACHE_DB' => 1,
            'REDIS_QUEUE_DB' => 2,
            'CACHE_STORE' => 'redis',
            'QUEUE_CONNECTION' => 'redis',
            'SESSION_DRIVER' => 'redis',
            'FILESYSTEM_DISK' => 'public',
            'MEDIA_DISK' => 'public',
        ];
    }

    private function applyRuntimeConfiguration(array $input, string $appKey): void
    {
        config([
            'app.key' => $appKey,
            'app.name' => $input['app_name'],
            'app.url' => rtrim($input['app_url'], '/'),
            'app.locale' => $input['app_locale'],
            'app.timezone' => $input['app_timezone'],
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => $input['db_host'],
            'database.connections.pgsql.port' => $input['db_port'],
            'database.connections.pgsql.database' => $input['db_database'],
            'database.connections.pgsql.username' => $input['db_username'],
            'database.connections.pgsql.password' => $input['db_password'] ?? '',
            'database.redis.client' => 'phpredis',
            'database.redis.default.host' => $input['redis_host'],
            'database.redis.default.port' => $input['redis_port'],
            'database.redis.default.password' => $input['redis_password'] ?? null,
            'database.redis.default.database' => $input['redis_db'],
            'database.redis.cache.host' => $input['redis_host'],
            'database.redis.cache.port' => $input['redis_port'],
            'database.redis.cache.password' => $input['redis_password'] ?? null,
            'database.redis.cache.database' => 1,
            'database.redis.queue.host' => $input['redis_host'],
            'database.redis.queue.port' => $input['redis_port'],
            'database.redis.queue.password' => $input['redis_password'] ?? null,
            'database.redis.queue.database' => 2,
            'cache.default' => 'redis',
            'queue.default' => 'redis',
            'session.driver' => 'redis',
            'filesystems.default' => 'public',
        ]);

        DB::purge('pgsql');
    }

    private function suggestedApplicationUrl(Request $request): string
    {
        $configuredUrl = rtrim((string) config('app.url'), '/');

        if (config('community.deployment_mode') === 'docker') {
            return $configuredUrl;
        }

        if (! $this->isLocalOrPrivateUrl($configuredUrl)) {
            return $configuredUrl;
        }

        $requestUrl = rtrim($request->getSchemeAndHttpHost(), '/');

        return $this->isLocalOrPrivateUrl($requestUrl) ? '' : $requestUrl;
    }

    private function isLocalOrPrivateUrl(string $url): bool
    {
        if ($url === '') {
            return true;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return true;
        }

        $host = trim($host, '[]');

        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
