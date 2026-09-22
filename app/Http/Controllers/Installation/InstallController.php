<?php

namespace App\Http\Controllers\Installation;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Installation\InstallApplicationRequest;
use App\Models\User;
use App\Support\Installation\EnvironmentFileWriter;
use App\Support\Installation\InstallationState;
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
    public function show(Request $request, InstallationState $state): View|RedirectResponse
    {
        if ($state->isInstalled()) {
            return redirect('/admin');
        }

        return view('install.index', [
            'currentRequestUrl' => rtrim($request->getSchemeAndHttpHost(), '/'),
            'defaultAppUrl' => rtrim((string) config('app.url'), '/'),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function store(
        InstallApplicationRequest $request,
        InstallationState $state,
        EnvironmentFileWriter $environment,
    ): RedirectResponse {
        if ($state->isInstalled()) {
            return redirect()->route('login');
        }

        try {
            return $state->exclusively(function () use ($request, $state, $environment) {
                if ($state->isInstalled()) {
                    return redirect('/admin');
                }

                $input = $request->validated();
                $appKey = $environment->write($this->environmentValues($input));
                $this->applyRuntimeConfiguration($input, $appKey);

                Artisan::call('migrate', ['--force' => true]);
                Artisan::call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

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
                ->withErrors(['installation' => 'Installation could not be completed. Review the latest file in storage/logs, correct the issue, and retry.']);
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
        ]);
    }
}
