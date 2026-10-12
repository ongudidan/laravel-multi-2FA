<?php

namespace Ongudidan\Multi2fa\Http\Controllers;

use Ongudidan\Multi2fa\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorSettingsController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactorService
    ) {}

    /**
     * Show the user's two-factor authentication settings page.
     */
    public function show(Request $request): mixed
    {
        if (!$this->twoFactorService->isAuthenticatorEnabled()) {
            abort(403, 'Two-factor authentication is globally disabled.');
        }

        $user = $request->user();
        if ($user) {
            $this->twoFactorService->ensureAuthenticatorSecret($user);
            $status = $this->twoFactorService->getAuthenticatorStatus($user);
        } else {
            $status = ['twoFactorEnabled' => false, 'requiresConfirmation' => false];
        }

        return $this->twoFactorService->renderView($request, 'two_factor_settings', [
            'twoFactorEnabled' => $status['twoFactorEnabled'],
            'requiresConfirmation' => $status['requiresConfirmation'],
        ]);
    }
}
