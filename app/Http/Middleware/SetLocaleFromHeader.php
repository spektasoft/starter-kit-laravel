<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var string */
        $defaultLocale = config('app.locale', 'en');
        /** @var array<string,string> */
        $supportedLocales = config('app.supported_locales', []);
        /** @var string */
        $locale = $request->header('Content-Language');

        if (! $locale || ! in_array($locale, $supportedLocales)) {
            $locale = $defaultLocale;
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
