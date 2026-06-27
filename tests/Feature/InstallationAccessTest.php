<?php

use App\Settings\GeneralSettings;
use App\Support\Installation\RequirementsChecker;

beforeEach(function () {
    $this->withoutVite();
});

it('renders the installer without loading database-backed settings', function () {
    config(['community.installed' => false]);
    @unlink(storage_path('app/installed'));

    $this->app->bind(
        GeneralSettings::class,
        fn () => throw new RuntimeException('Settings must not load before installation.'),
    );

    $this->get('/install')
        ->assertOk()
        ->assertSee('Install WeblexAI Community Edition');
});

it('hides managed infrastructure fields in docker deployments', function () {
    config([
        'app.url' => 'http://localhost:8787',
        'community.installed' => false,
        'community.deployment_mode' => 'docker',
    ]);
    @unlink(storage_path('app/installed'));

    $this->get('/install')
        ->assertOk()
        ->assertSee('Managed Docker services')
        ->assertSee('value="http://localhost:8787"', false)
        ->assertDontSee('name="db_host"', false)
        ->assertDontSee('name="redis_host"', false);
});

it('shows infrastructure fields in traditional deployments', function () {
    config([
        'community.installed' => false,
        'community.deployment_mode' => 'traditional',
    ]);
    @unlink(storage_path('app/installed'));

    $this->get('/install')
        ->assertOk()
        ->assertSee('name="db_host"', false)
        ->assertSee('name="redis_host"', false);
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

it('renders indexed infrastructure errors after failed connection checks', function () {
    config([
        'community.installed' => false,
        'community.deployment_mode' => 'traditional',
    ]);
    @unlink(storage_path('app/installed'));

    $checker = Mockery::mock(RequirementsChecker::class);
    $checker->shouldReceive('system')->twice()->andReturn([]);
    $checker->shouldReceive('infrastructure')->once()->andReturn([
        [
            'name' => 'PostgreSQL connection',
            'passed' => false,
            'detail' => 'Check the PostgreSQL connection.',
        ],
        [
            'name' => 'Redis connection',
            'passed' => false,
            'detail' => 'Check the Redis connection.',
        ],
    ]);
    $this->app->instance(RequirementsChecker::class, $checker);

    $response = $this
        ->from(route('install.show'))
        ->post(route('install.store'), [
            'app_name' => 'WeblexAI Community Edition',
            'app_url' => 'http://localhost:8787',
            'app_locale' => 'en',
            'app_timezone' => 'UTC',
            'db_host' => 'postgres',
            'db_port' => 5432,
            'db_database' => 'weblex',
            'db_username' => 'weblex',
            'db_password' => 'database-password',
            'redis_host' => 'redis',
            'redis_port' => 6379,
            'redis_password' => null,
            'redis_db' => 0,
            'admin_name' => 'Administrator',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'AdminPassword123!',
            'admin_password_confirmation' => 'AdminPassword123!',
            'filesystem_disk' => 'public',
        ]);

    $response
        ->assertRedirect(route('install.show'))
        ->assertSessionHasErrors('infrastructure');

    expect(session('errors')->get('infrastructure'))->toBe([
        'PostgreSQL connection: Check the PostgreSQL connection.',
        'Redis connection: Check the Redis connection.',
    ]);

    $this->get(route('install.show'))
        ->assertOk()
        ->assertSee('PostgreSQL connection: Check the PostgreSQL connection.')
        ->assertSee('Redis connection: Check the Redis connection.');
});
