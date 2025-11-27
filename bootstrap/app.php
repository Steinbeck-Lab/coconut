<?php

use App\Http\Middleware\TrustProxies;
use BezhanSalleh\FilamentExceptions\FilamentExceptions;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Csp\AddCspHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register your own TrustProxies class as the proxy middleware
        // Global middleware applied to everything
        $middleware->use([
            TrustProxies::class,
        ]);

        // Append CSP to the *web* middleware group (don't replace the entire group)
        $middleware->appendToGroup('web', [
            AddCspHeaders::class,
        ]);

        // Optional aliases
        // $middleware->alias([
        //     'verified' => EnsureEmailOrPhoneIsVerified::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (Throwable $e) {
            FilamentExceptions::report($e);
        });
    })
    ->create();
