<?php

use App\Http\Middleware\SecureHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('does not upgrade requests when the application is served over HTTP', function () {
    $app = app();
    $app->detectEnvironment(fn (): string => 'production');
    $request = Request::create('http://localhost:8787/projects');

    $response = app(SecureHeaders::class)->handle($request, fn (): Response => new Response);

    expect($response->headers->get('Content-Security-Policy'))
        ->not->toContain('upgrade-insecure-requests')
        ->and($response->headers->has('Strict-Transport-Security'))
        ->toBeFalse();

    $app->detectEnvironment(fn (): string => 'testing');
});

it('enables transport security when the application is served over HTTPS', function () {
    $app = app();
    $app->detectEnvironment(fn (): string => 'production');
    $request = Request::create('https://translations.example.com/projects');

    $response = app(SecureHeaders::class)->handle($request, fn (): Response => new Response);

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain('upgrade-insecure-requests')
        ->and($response->headers->has('Strict-Transport-Security'))
        ->toBeTrue();

    $app->detectEnvironment(fn (): string => 'testing');
});
