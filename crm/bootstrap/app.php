<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetAppLocale;
use App\Services\SystemErrorLoggerService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'locale']);

        $middleware->preventRequestForgery(except: ['pension-coefficients*']);

        $middleware->web(append: [
            SetAppLocale::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->report(function (Throwable $exception) {
            Log::error($exception->getMessage(), [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);
        });

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            // Log server and client errors (4xx & 5xx) into DB and notify admin
            if ($status >= 400) {
                app(SystemErrorLoggerService::class)->logException($exception, $request, $status);
            }

            if ($status === HttpResponse::HTTP_NOT_FOUND && ! $request->expectsJson() && ! $request->is('api/*')) {
                return Inertia::render('Error', ['status' => HttpResponse::HTTP_NOT_FOUND])
                    ->toResponse($request)
                    ->setStatusCode(HttpResponse::HTTP_NOT_FOUND);
            }

            if ($status >= 500 && ! $request->expectsJson() && ! $request->is('api/*')) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
