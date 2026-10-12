<?php

namespace Ongudidan\Multi2fa\Actions;

use Ongudidan\Multi2fa\Services\TwoFactorService;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable as BaseAction;

class RedirectIfTwoFactorAuthenticatable extends BaseAction
{
    /**
     * Handle the incoming request.
     */
    public function handle($request, $next)
    {
        $twoFactorService = app(TwoFactorService::class);

        // If two factor authenticator is globally disabled, skip the 2FA redirect check completely
        if (!$twoFactorService->isAuthenticatorEnabled()) {
            return $next($request);
        }

        // Otherwise, run parent's handle logic
        return parent::handle($request, $next);
    }
}
