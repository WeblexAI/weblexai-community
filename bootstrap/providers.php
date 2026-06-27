<?php

use App\Providers\AppServiceProvider;
use App\Providers\CacheServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\ResponseServiceProvider;
use Laravel\Boost\BoostServiceProvider;

return [
    AppServiceProvider::class,
    CacheServiceProvider::class,
    AdminPanelProvider::class,
    HorizonServiceProvider::class,
    ResponseServiceProvider::class,
    BoostServiceProvider::class,
];
