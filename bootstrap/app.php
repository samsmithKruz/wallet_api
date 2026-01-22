<?php

use App\Http\Middleware\TokenAuthMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        $middleware->alias([
            'token.auth' => TokenAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
        $exceptions->render(function (Throwable $e, Request $request) {
            // Only format JSON responses for API routes
            if ($request->expectsJson() || $request->is('api/*')) {
                $statusCode = ($e instanceof HttpException)
                    ? $e->getStatusCode()
                    : 500;

                $message = $e->getMessage() ?: 'An error occurred';

                $response = [
                    'status_code' => $statusCode,
                    'message' => $message,
                    'data' => null,
                    'errors' => [$message]
                ];

                // Add debug info in local environment
                if (app()->environment('local')) {
                    $response['debug'] = [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ];
                }

                return response()->json($response, $statusCode);
            }

            // Return null to let Laravel handle non-API exceptions normally
            return null;
        });
    })->create();
