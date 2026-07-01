<?php

namespace App\Providers;

use App\Services\Auth\AuthProviderInterface;
use App\Services\Auth\AuthService;
use App\Services\Auth\DotNetAuthProvider;
use App\Services\Auth\LaravelAuthProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Auth Adapter Pattern - bind the correct provider based on config
        $this->app->singleton(AuthProviderInterface::class, function () {
            return match (config('wrkplan.auth.provider', 'laravel')) {
                'dotnet' => new DotNetAuthProvider(),
                default  => new LaravelAuthProvider(),
            };
        });

        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService($app->make(AuthProviderInterface::class));
        });
    }

    public function boot(): void
    {
        //
    }
}

