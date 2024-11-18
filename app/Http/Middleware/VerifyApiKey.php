<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->validate([
            'api_key' => 'required',
        ]);

        /** @var string */
        $requestApiKey = $request->input('api_key');
        /** @var string|null */
        $apiKey = config('api.key');

        if (! $apiKey || $apiKey !== $requestApiKey) {
            $message = __('api.invalid_api_key');

            return response()->json([
                'errors' => [$message],
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
