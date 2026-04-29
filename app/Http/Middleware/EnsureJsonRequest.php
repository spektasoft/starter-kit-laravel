<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJsonRequest
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->wantsJson()) {
            return response()->json(['error' => __('The given data was invalid.')], Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        return $next($request);
    }
}
