<?php

namespace App\Providers;

use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\ServiceProvider;

class ResponseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        ResponseFactory::macro('success', function (string $message = 'Success', array $data = []) {
            return back()->with([
                'success' => true,
                'message' => $message,
                'data' => $data,
            ]);
        });

        ResponseFactory::macro('error', function (string $message = 'An error occurred', int $code = 400) {
            return back()->with([
                'success' => false,
                'message' => $message,
            ], $code);
        });
    }
}
