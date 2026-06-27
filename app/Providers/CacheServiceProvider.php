<?php

namespace App\Providers;

use App\Services\Cache\ConfigCacheInvalidationService;
use App\Services\Cache\ConfigCacheStore;
use App\Services\Cache\TranslationCacheInvalidationService;
use App\Services\Cache\TranslationCacheStore;
use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConfigCacheStore::class);
        $this->app->singleton(ConfigCacheInvalidationService::class);
        $this->app->singleton(TranslationCacheStore::class);
        $this->app->singleton(TranslationCacheInvalidationService::class);
    }
}
