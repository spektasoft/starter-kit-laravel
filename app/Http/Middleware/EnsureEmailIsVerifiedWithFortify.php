<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Features as FortifyFeatures;

class EnsureEmailIsVerifiedWithFortify extends EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  string|null  $redirectToRoute
     * @return Response|RedirectResponse|null
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        // If user is guest or Fortify's email verification feature is not enabled,
        // simply pass the request to the next middleware without
        // enforcing email verification.
        if (! Auth::check() || ! FortifyFeatures::enabled(FortifyFeatures::emailVerification())) {
            /** @var Response|RedirectResponse|null */
            $response = $next($request);

            return $response;
        }

        // Otherwise, then call the parent's handle method which
        // contains the original email verification logic.
        return parent::handle($request, $next, $redirectToRoute);
    }
}
