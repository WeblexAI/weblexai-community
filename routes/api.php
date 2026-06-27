<?php

use App\Http\Controllers\CDN\ConfigController;
use App\Http\Controllers\CDN\StreamingTranslationController;
use Illuminate\Support\Facades\Route;

Route::prefix('project')
    ->middleware(['throttle:translation-api', 'project-auth', 'api-security', 'log-api'])
    ->group(function (): void {
        Route::get('config', ConfigController::class);
        Route::post('translations', StreamingTranslationController::class);
    });
