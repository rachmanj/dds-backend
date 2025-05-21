<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Enable sessions for API routes
        $middleware->statefulApi();

        // Add session middleware to API group
        $middleware->group('api', [
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\ForceJsonResponse::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Configure web middleware
        $middleware->web(\Illuminate\Cookie\Middleware\EncryptCookies::class);
        $middleware->web(\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class);
        $middleware->web(\Illuminate\Session\Middleware\StartSession::class);
        $middleware->web(\Illuminate\View\Middleware\ShareErrorsFromSession::class);
        $middleware->web(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $middleware->web(\Illuminate\Routing\Middleware\SubstituteBindings::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
