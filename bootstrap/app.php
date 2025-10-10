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
        $middleware->alias([
            'check.account.status' => \App\Http\Middleware\CheckAccountStatus::class,
            'check.profile.completion' => \App\Http\Middleware\CheckProfileCompletion::class,
            'check.terms.accepted' => \App\Http\Middleware\CheckTermsAccepted::class,
            'check.session.validity' => \App\Http\Middleware\CheckSessionValidity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
