<?php

use App\Exceptions\Handler;
use App\Http\Middleware\ApiGuest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
//        $middleware->api(prepend: [
//            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
//        ]);

        $middleware->alias([
            'api_guest' => ApiGuest::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (\Throwable $exception, Request $request) {
            return (new Handler)->render($request, $exception);
        });
    })->create();
