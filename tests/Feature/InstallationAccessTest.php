<?php

use App\Http\Requests\Installation\InstallApplicationRequest;
use App\Settings\CacheSettings;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->withoutVite();
});

it('renders the Docker installer without loading database-backed settings', function () {
    config([
        'app.url' => 'http://localhost:8787',
        'community.installed' => false,
    ]);
    @unlink(storage_path('app/installed'));

    $this->app->bind(
        CacheSettings::class,
        fn () => throw new RuntimeException('Settings must not load before installation.'),
    );

    $this->get('/install')
        ->assertOk()
        ->assertSee('Install WeblexAI Community Edition')
        ->assertSee('Create your account')
        ->assertDontSee('Docker services are ready')
        ->assertSee('value="http://localhost:8787"', false)
        ->assertDontSee('name="db_host"', false)
        ->assertDontSee('name="redis_host"', false)
        ->assertDontSee('Traditional install');
});

it('validates the administrator password during account setup', function (string $password, string $confirmation, bool $valid) {
    Route::post('/_test/install-validation', fn (InstallApplicationRequest $request) => response()->noContent());

    $response = $this->postJson('/_test/install-validation', [
        'app_name' => 'WeblexAI',
        'app_url' => 'https://weblex.test',
        'app_locale' => 'en',
        'app_timezone' => 'UTC',
        'admin_name' => 'Administrator',
        'admin_email' => 'admin@example.com',
        'admin_password' => $password,
        'admin_password_confirmation' => $confirmation,
    ]);

    if ($valid) {
        $response->assertNoContent();
    } else {
        $response->assertUnprocessable()->assertJsonValidationErrors('admin_password');
    }
})->with([
    'six characters' => ['Ab1!cd', 'Ab1!cd', true],
    'five characters' => ['Ab1!c', 'Ab1!c', false],
    'confirmation mismatch' => ['Ab1!cd', 'Ab1!ce', false],
    'missing symbol' => ['Ab12cd', 'Ab12cd', false],
]);

it('redirects an uninstalled browser request to the installer', function () {
    config(['community.installed' => false]);
    @unlink(storage_path('app/installed'));

    $this->get('/login')->assertRedirect(route('install.show'));
});

it('returns a service unavailable response for an uninstalled API request', function () {
    config(['community.installed' => false]);
    @unlink(storage_path('app/installed'));

    $this->getJson('/api/project/config')
        ->assertStatus(503)
        ->assertExactJson(['message' => 'Application installation is required.']);
});

it('locks the installer after installation', function () {
    config(['community.installed' => true]);

    $this->get('/install')->assertRedirect('/admin');
});
