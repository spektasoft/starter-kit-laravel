<?php

use App\Http\Middleware\EnsureEmailIsVerifiedWithFortify;
use App\Http\Middleware\EnsureJsonRequest;
use App\Http\Middleware\SetDeviceFromHeader;
use App\Http\Middleware\SetLocaleFromHeader;
use App\Http\Middleware\SetLocaleFromQueryAndSession;
use App\Http\Middleware\VerifyApiArtisan;
use App\Http\Middleware\VerifyApiKey;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Honeypot\ProtectAgainstSpam;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(ProtectAgainstSpam::class);
        $middleware->append(SetDeviceFromHeader::class);
        $middleware->append(SetLocaleFromHeader::class);
        $middleware->web(append: [
            SetLocaleFromQueryAndSession::class,
        ]);
        $middleware->statefulApi();

        $middleware->alias([
            'json' => EnsureJsonRequest::class,
            'verify.api.artisan' => VerifyApiArtisan::class,
            'verify.api.key' => VerifyApiKey::class,
            'verified' => EnsureEmailIsVerifiedWithFortify::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
