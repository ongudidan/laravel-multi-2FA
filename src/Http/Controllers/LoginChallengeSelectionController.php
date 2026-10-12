<?php

namespace Ongudidan\Multi2fa\Http\Controllers;

use Ongudidan\Multi2fa\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LoginChallengeSelectionController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactorService
    ) {}

    /**
     * Show the verification method selection screen.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        if (!$request->session()->has('login_challenge.id')) {
            $userId = $request->session()->get('login_otp.id') ?? $request->session()->get('login.id');
            if (!$userId) {
                return redirect('/login');
            }

            $remember = $request->session()->get('login_otp.remember') 
                ?? $request->session()->get('login.remember') 
                ?? false;

            $this->twoFactorService->initiateChallengeSelection($request, $userId, (bool) $remember);
        }

        $userId = $request->session()->get('login_challenge.id');
        $userModel = config('auth.providers.users.model', 'App\\Models\\User');
        $user = $userModel::find($userId);

        $availableMethods = $user ? $this->twoFactorService->getAvailableMethodsForUser($user) : ['email', 'authenticator'];

        return $this->twoFactorService->renderView($request, 'login_challenge', [
            'availableMethods' => $availableMethods,
        ]);
    }

    /**
     * Handle the user's selected verification method.
     */
    public function select(Request $request): RedirectResponse
    {
        $request->validate([
            'method' => 'required|string|in:email,authenticator',
        ]);

        $redirectUrl = $this->twoFactorService->processChallengeSelection($request, $request->input('method'));

        return redirect($redirectUrl);
    }
}
