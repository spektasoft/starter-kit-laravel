<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiArtisan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $url = $request->url();

        if (str_contains($url, 'artisan')) {
            $apiArtisan = boolval(config('api.artisan', false));
            if (! $apiArtisan) {
                $message = __('api.artisan_disabled');

                return response()->json([
                    'errors' => [$message],
                ], Response::HTTP_METHOD_NOT_ALLOWED);
            }

            return $next($request);
        }

        return $next($request);
    }
}
