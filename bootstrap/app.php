<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(HandleCors::class);

        $middleware->group('api', [
            fn ($req, $next) => tap($next($req), function ($res) {
                $res->headers->set('Access-Control-Allow-Origin', 'http://localhost:3000');
                $res->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
                $res->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
                $res->headers->set('Access-Control-Allow-Credentials', 'true');
            })
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
