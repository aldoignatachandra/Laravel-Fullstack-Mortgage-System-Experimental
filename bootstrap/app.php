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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            '/mortgage/interest/payment/midtrans/notification',
        ]);
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Custom error handling for 403 Forbidden
        $exceptions->respond(function ($response, $exception, $request) {
            // Handle 403 Forbidden
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException ||
                $exception instanceof \Illuminate\Auth\Access\AuthorizationException) {

                // API requests should get JSON
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access denied. You do not have permission to perform this action.',
                        'code' => 403,
                    ], 403);
                }

                // Web requests get the custom view
                return response()->view('errors.403', [], 403);
            }

            return $response;
        });
    })->create();
