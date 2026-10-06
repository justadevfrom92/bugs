<?php

use App\Http\Middleware\EnsureAppAccess;
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
        // My Account (customers) and the admin (employees) each send people to their own sign-in page
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('myaccount*') ? route('myaccount.login') : route('admin.login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('myaccount*') ? route('myaccount.dashboard') : route('admin.launcher'));
        $middleware->alias([
            'app' => EnsureAppAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
