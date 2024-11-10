<?php

use App\Http\Middleware\SetLocaleFromQueryAndSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [SetLocaleFromQueryAndSession::class]);
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            /** @var string */
            $defaultLang = config('app.locale', 'en');
            /** @var string */
            $appName = config('app.name', 'Laravel');
            $slug = Str::slug($appName, '_');
            $cookieKey = $slug.'_lang';
            /** @var string */
            $lang = $request->cookie($cookieKey, $defaultLang);
            app()->setLocale($lang);
        });
    })->create();
