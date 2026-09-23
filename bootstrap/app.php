<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            \App\Http\Middleware\CanonicalHost::class,
        ], append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\RememberLeadSource::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Foutbewaking: in productie een (gedempte) mail naar de beheerder.
        // 404's, validatiefouten e.d. worden door Laravel al niet "gerapporteerd".
        $exceptions->report(fn (\Throwable $e) => \App\Support\ErrorAlert::exception($e));

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
