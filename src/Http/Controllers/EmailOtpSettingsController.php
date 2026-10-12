<?php

namespace Ongudidan\Multi2fa\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ongudidan\Multi2fa\Services\TwoFactorService;

class EmailOtpSettingsController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactorService
    ) {}

    /**
     * Show the user's email OTP settings page.
     */
    public function show(Request $request): mixed
    {
        if (!$this->twoFactorService->isEmailOtpEnabled()) {
            abort(403, 'Email OTP authentication is globally disabled.');
        }

        $user = $request->user();
        $isEnabled = $this->twoFactorService->userHasEmailOtp($user);

        return $this->twoFactorService->renderView($request, 'email_otp_settings', [
            'user' => $user,
            'emailOtpEnabled' => $isEnabled,
        ]);
    }

    /**
     * Update the user's email OTP setting.
     */
    public function update(Request $request): mixed
    {
        if (!$this->twoFactorService->isEmailOtpEnabled()) {
            abort(403, 'Email OTP authentication is globally disabled.');
        }

        $user = $request->user();
        $enable = $request->boolean('enable');

        $user->email_otp_enabled = $enable;
        $user->save();

        $message = $enable 
            ? 'Email OTP verification has been enabled.' 
            : 'Email OTP verification has been disabled.';

        return back()->with('status', $message);
    }
}
