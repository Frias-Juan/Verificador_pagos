<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 1. Forzar HTTPS si la URL del .env lo tiene
        if (str_contains(config('app.url'), 'https')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        // 2. Compatibilidad con base de datos (por el error 1273 que tenías antes)
        Schema::defaultStringLength(191);

        // 3. Observadores
        \App\Models\User::observe(\App\Observers\UserObserver::class);
    }
}