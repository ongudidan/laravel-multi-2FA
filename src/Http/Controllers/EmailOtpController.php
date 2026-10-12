<?php

namespace Ongudidan\Multi2fa\Http\Controllers;

use Ongudidan\Multi2fa\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EmailOtpController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactorService
    ) {}

    /**
     * Show the email OTP verification form.
     */
    public function show(Request $request): mixed
    {
        if (!$request->session()->has('login_otp.id')) {
            return redirect('/login');
        }

        $userId = $request->session()->get('login_otp.id');
        $userModel = config('auth.providers.users.model', 'App\\Models\\User');
        $user = $userModel::find($userId);

        $canSwitch = $user ? $this->twoFactorService->canSwitchMethods($user, 'email') : false;

        return $this->twoFactorService->renderView($request, 'email_otp', [
            'canSwitch' => $canSwitch,
        ]);
    }

    /**
     * Verify the email OTP code.
     */
    public function verify(Request $request): SymfonyResponse
    {
        if (!$request->session()->has('login_otp.id')) {
            return redirect('/login');
        }

        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $this->twoFactorService->verifyLoginOtp($request, $request->code);
        $this->twoFactorService->completeEmailOtpLogin($request, $user);

        $home = config('multi-2fa.home') ?? config('fortify.home', '/dashboard');

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($home);
    }

    /**
     * Resend a verification OTP.
     */
    public function resend(Request $request): RedirectResponse
    {
        if (!$request->session()->has('login_otp.id')) {
            return redirect('/login');
        }

        $user = $this->twoFactorService->resendLoginOtp($request);
        if (!$user) {
            return redirect('/login');
        }

        return back()->with('success', 'A new verification code has been sent to your email.');
    }
}
