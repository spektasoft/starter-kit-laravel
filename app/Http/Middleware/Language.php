<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var string|null */
        $lang = $request->query('lang');
        /** @var string */
        $appName = config('app.name', 'Laravel');
        $slug = Str::slug($appName, '_');
        $cookieKey = $slug.'_lang';

        if (isset($lang)) {
            app()->setLocale($lang);
            $url = $request->fullUrlWithoutQuery('lang');

            return redirect($url)->withCookie(cookie($cookieKey, $lang));
        }

        /** @var string */
        $defaultLang = config('app.locale', 'en');
        /** @var string */
        $lang = $request->cookie($cookieKey, $defaultLang);
        app()->setLocale($lang);

        return $next($request);
    }
}
