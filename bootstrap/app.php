<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register Backpack admin middleware
        $middleware->alias([
            'admin' => \App\Http\Middleware\CheckIfAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Force JSON responses for API routes
        $exceptions->shouldRenderJsonWhen(function ($request, Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->expectsJson();
        });
        
        // Enable detailed error reporting for debugging
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('admin/*') && config('app.debug')) {
                // Log the error for debugging
                \Log::error('Admin route error: ' . $e->getMessage(), [
                    'exception' => $e,
                    'url' => $request->url(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });
    })->create();
