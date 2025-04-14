<?php

namespace App\Http\Middleware;

use App\Utils\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetDeviceFromHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = $request->header('User-Agent');
        if ($userAgent && str_contains($userAgent, 'wv')) {
            Device::setIsAndroid(true);
        }

        $requestedWithHeader = $request->header('x-requested-with');
        if ($requestedWithHeader) {
            Device::setRequestedWith($requestedWithHeader);
        }

        return $next($request);
    }
}
