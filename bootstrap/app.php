<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware(['web'])
                ->group(base_path('routes/helal.php'));
        }
    )
    ->withBroadcasting(
        __DIR__ . '/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['auth:api']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role_check' => \App\Http\Middleware\RoleCheckMiddleWare::class,
            'check_anonymous_user' => \App\Http\Middleware\checkAnonymousUser::class,
            'check_is_user' => \App\Http\Middleware\CheckIsUser::class,
            'check_is_entertrainer' => \App\Http\Middleware\CheckIsEntertrainer::class,
            'check_is_venue_holder' => \App\Http\Middleware\CheckIsVenueHolder::class,
            'check_is_user_or_entertainer_or_venue_holder' => \App\Http\Middleware\CheckIsUserOrEntertainerOrVenueHolder::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
