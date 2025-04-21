<?php

namespace App\Http\Middleware;

use App\Services\DeviceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetDeviceFromHeader
{
    protected DeviceService $deviceService;

    public function __construct(DeviceService $deviceService)
    {
        $this->deviceService = $deviceService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = $request->header('User-Agent');
        if ($userAgent && str_contains($userAgent, 'wv')) {
            $this->deviceService->setIsAndroid(true);
        }

        $requestedWithHeader = $request->header('x-requested-with');
        if ($requestedWithHeader) {
            $this->deviceService->setRequestedWith($requestedWithHeader);
        }

        return $next($request);
    }
}
