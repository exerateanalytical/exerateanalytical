<?php

use App\Exceptions\DataIntegrityException;
use App\Exceptions\LegalRiskException;
use App\Exceptions\ScoreComputationException;
use App\Exceptions\UnauthorizedCountryAccessException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (UnauthorizedCountryAccessException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'errors' => [],
                    'code' => 403,
                ], 403);
            }
        });

        $exceptions->render(function (DataIntegrityException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'errors' => [],
                    'code' => 422,
                ], 422);
            }
        });

        $exceptions->render(function (LegalRiskException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'errors' => [],
                    'code' => 422,
                ], 422);
            }
        });

        $exceptions->render(function (ScoreComputationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'errors' => [],
                    'code' => 500,
                ], 500);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Resource not found.',
                    'errors' => [],
                    'code' => 404,
                ], 404);
            }
        });
    })->create();
