<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Redirect unauthenticated guests to our named login route
        $middleware->redirectGuestsTo(fn () => route('auth.login'));

        $middleware->alias([
            'admin.only'   => \App\Http\Middleware\AdminOnly::class,
            'api.token.auth' => \App\Http\Middleware\ApiTokenAuth::class,
            'tenant.scope' => \App\Http\Middleware\TenantScope::class,
            'audit'        => \App\Http\Middleware\AuditActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null;
            }

            return null;
        });

        $exceptions->respond(function ($response, $exception, Request $request) {
            if ($response->getStatusCode() !== 419 || $request->expectsJson() || $request->is('api/*')) {
                return $response;
            }

            return redirect()
                ->route('auth.login')
                ->withInput($request->except('password'))
                ->with('auth_popup', [
                    'type' => 'warning',
                    'title' => 'Session expired',
                    'message' => 'Your login session expired before submission. Please try again.',
                ]);
        });
    })->create();
