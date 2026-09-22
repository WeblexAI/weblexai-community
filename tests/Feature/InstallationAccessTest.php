<?php

use App\Settings\CacheSettings;

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
        ->assertSee('Docker services are ready')
        ->assertSee('value="http://localhost:8787"', false)
        ->assertDontSee('name="db_host"', false)
        ->assertDontSee('name="redis_host"', false)
        ->assertDontSee('Traditional install');
});

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
