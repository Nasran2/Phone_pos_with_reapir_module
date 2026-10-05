<?php

use App\Http\Middleware\EnforceFeatureAvailability;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RequireLorryUser;
use App\Http\Middleware\RequireRepairEnabled;
use App\Http\Middleware\RequireWholesaleEnabled;
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
        $middleware->web(append: [EnforceFeatureAvailability::class]);
        $middleware->alias([
            'repair.enabled' => RequireRepairEnabled::class,
            'wholesale.enabled' => RequireWholesaleEnabled::class,
            'permission' => PermissionMiddleware::class,
            'lorry.user' => RequireLorryUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['unlock_secret', 'device.unlock_secret']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
