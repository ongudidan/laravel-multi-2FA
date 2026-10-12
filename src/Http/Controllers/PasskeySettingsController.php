<?php

namespace Ongudidan\Multi2fa\Http\Controllers;

use Ongudidan\Multi2fa\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Passkeys\Passkey;

class PasskeySettingsController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactorService
    ) {}

    /**
     * Display the user's passkeys management page.
     */
    public function index(Request $request): mixed
    {
        return $this->twoFactorService->renderView($request, 'passkeys_settings', [
            'passkeys' => $this->twoFactorService->getUserPasskeys($request->user()),
            'status' => session('status'),
        ]);
    }

    /**
     * Revoke / delete a user passkey.
     */
    public function destroy(Request $request, Passkey $passkey): RedirectResponse
    {
        $this->twoFactorService->deletePasskey($request->user(), $passkey);

        return redirect()->back()->with('status', 'Passkey revoked successfully.');
    }
}
