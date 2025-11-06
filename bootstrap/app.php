<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register your custom middleware
        $middleware->alias([
            'role' => App\Http\Middleware\RoleMiddleware::class,
            'kiosk' => App\Http\Middleware\KioskMiddleware::class,

        ]);

        // You can also add other middleware registrations here
        $middleware->web(append: [
            // ... other middleware

        ]);

        $middleware->api(append: [
            // ... other middleware  
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();