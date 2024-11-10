<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromQueryAndSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var array<string,string> */
        $supportedLocales = config('app.supported_locales', []);

        /** @var string|null */
        $lang = $request->query('lang');

        if ($lang && in_array($lang, $supportedLocales)) {
            session(['locale' => $lang]);
            app()->setLocale($lang);

            return redirect($request->fullUrlWithoutQuery('lang'));
        }

        /** @var string */
        $defaultLang = config('app.locale', 'en');
        /** @var string */
        $lang = session('locale', $defaultLang);
        if (! in_array($lang, $supportedLocales)) {
            $lang = $defaultLang;
        }
        session()->put('locale', $lang);
        app()->setLocale($lang);

        return $next($request);
    }
}
