<?php

namespace Ongudidan\Multi2fa\Http\Middleware;

use Ongudidan\Multi2fa\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabledGlobally
{
    public function __construct(
        protected TwoFactorService $twoFactorService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only run check on two-factor related routes
        if ($request->is('user/two-factor-*') || 
            $request->is('user/confirmed-two-factor-*') || 
            $request->is('settings/two-factor*') ||
            $request->is('two-factor-challenge')) {

            if (!$this->twoFactorService->isAuthenticatorEnabled()) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Two-factor authentication is globally disabled.'], 403);
                }
                abort(403, 'Two-factor authentication is globally disabled.');
            }
        }

        return $next($request);
    }
}
