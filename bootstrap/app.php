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
        $middleware->appendToGroup('api', \App\Http\Middleware\RequestContext::class);
        $middleware->alias([
            'jwt' => \App\Http\Middleware\JwtAuth::class,
            'admin.basic' => \App\Http\Middleware\AdminBasicAuth::class,
            'admin.role' => \App\Http\Middleware\AdminRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            $isApi = $request->expectsJson() || str_starts_with($request->path(), 'api/');
            if (!$isApi) {
                return null;
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed.',
                    'fieldErrors' => $e->errors(),
                ], 422);
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json([
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ], 401);
            }

            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return response()->json([
                    'code' => 'FORBIDDEN',
                    'message' => 'Access denied.',
                ], 403);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return response()->json([
                    'code' => 'NOT_FOUND',
                    'message' => 'Resource not found.',
                ], 404);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                return response()->json([
                    'code' => 'METHOD_NOT_ALLOWED',
                    'message' => 'Method not allowed.',
                ], 405);
            }

            return response()->json([
                'code' => 'SERVER_ERROR',
                'message' => $e->getMessage() ?: 'Server error.',
            ], 500);
        });
    })->create();
