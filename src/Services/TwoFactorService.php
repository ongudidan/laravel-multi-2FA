<?php

namespace Ongudidan\Multi2fa\Services;

use Ongudidan\Multi2fa\Contracts\SettingsResolverInterface;
use Ongudidan\Multi2fa\Mail\LoginOtpMail;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Passkey;

class TwoFactorService
{
    public function __construct(
        protected SettingsResolverInterface $settingsResolver
    ) {}

    /**
     * Get settings resolver instance.
     */
    public function getSettingsResolver(): SettingsResolverInterface
    {
        return $this->settingsResolver;
    }

    /**
     * Get all raw security settings.
     */
    public function getSecuritySettings(): array
    {
        return $this->settingsResolver->getSettings();
    }

    /**
     * Clear cached security settings.
     */
    public function clearSettingsCache(): void
    {
        $this->settingsResolver->clearCache();
    }

    /**
     * Check if Email OTP is enabled globally.
     */
    public function isEmailOtpEnabled(): bool
    {
        return $this->settingsResolver->isEmailOtpEnabled();
    }

    /**
     * Check if Authenticator App (TOTP) is enabled globally.
     */
    public function isAuthenticatorEnabled(): bool
    {
        return $this->settingsResolver->isAuthenticatorEnabled();
    }

    /**
     * Check if Passkeys are enabled globally.
     */
    public function isPasskeyEnabled(): bool
    {
        return $this->settingsResolver->isPasskeyEnabled();
    }

    // --------------------------------------------------------------------------
    // User Method Verification & Enrollment
    // --------------------------------------------------------------------------

    /**
     * Check if the user has Authenticator App (TOTP) active and confirmed.
     */
    public function userHasAuthenticator(?Authenticatable $user): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'hasEnabledTwoFactorAuthentication') && $user->hasEnabledTwoFactorAuthentication()) {
            return true;
        }

        if (class_exists(Fortify::class) && method_exists(Fortify::class, 'confirmsTwoFactorAuthentication') && Fortify::confirmsTwoFactorAuthentication()) {
            return $user->two_factor_secret && !is_null($user->two_factor_confirmed_at);
        }

        return (bool) ($user->two_factor_secret ?? false);
    }

    /**
     * Check if the user has any Passkeys registered.
     */
    public function userHasPasskeys(?Authenticatable $user): bool
    {
        if (!$user || !Schema::hasTable('passkeys')) {
            return false;
        }

        if (method_exists($user, 'passkeys')) {
            return $user->passkeys()->exists();
        }

        return false;
    }

    /**
     * Check if the user has Email OTP enabled.
     */
    public function userHasEmailOtp(?Authenticatable $user): bool
    {
        if (!$user || empty($user->email)) {
            return false;
        }

        if (method_exists($user, 'hasEnabledEmailOtp')) {
            return (bool) $user->hasEnabledEmailOtp();
        }

        if (isset($user->email_otp_enabled)) {
            return (bool) $user->email_otp_enabled;
        }

        return false;
    }

    /**
     * Get all active 2FA methods for a given user based on settings and enrollment.
     *
     * @return array<string> List of available methods (e.g. ['passkey', 'authenticator', 'email'])
     */
    public function getAvailableMethodsForUser(?Authenticatable $user): array
    {
        if (!$user) {
            return [];
        }

        $methods = [];

        // 1. Passkey
        if ($this->isPasskeyEnabled() && $this->userHasPasskeys($user)) {
            $methods[] = 'passkey';
        }

        // 2. Authenticator App (TOTP)
        if ($this->isAuthenticatorEnabled() && $this->userHasAuthenticator($user)) {
            $methods[] = 'authenticator';
        }

        // 3. Email OTP
        if ($this->isEmailOtpEnabled() && $this->userHasEmailOtp($user)) {
            $methods[] = 'email';
        }

        return $methods;
    }

    /**
     * Determine if a user can switch to another 2FA method during challenge.
     */
    public function canSwitchMethods(?Authenticatable $user, string $currentMethod = ''): bool
    {
        if (!$user) {
            return false;
        }

        $methods = $this->getAvailableMethodsForUser($user);

        if ($currentMethod !== '') {
            $otherMethods = array_filter($methods, fn($m) => $m !== $currentMethod);
            return count($otherMethods) > 0;
        }

        return count($methods) > 1;
    }

    // --------------------------------------------------------------------------
    // Email OTP Lifecycle
    // --------------------------------------------------------------------------

    /**
     * Generate an OTP code, store it in the session, and send the email.
     */
    public function sendLoginOtp(Authenticatable $user, Request $request, bool $remember = false): int
    {
        $otp = random_int(100000, 999999);
        $expiryMinutes = (int) config('multi-2fa.otp_expiry_minutes', 10);

        $request->session()->put([
            'login_otp.id' => $user->getAuthIdentifier(),
            'login_otp.remember' => $remember,
            'login_otp.code' => $otp,
            'login_otp.expires_at' => now()->addMinutes($expiryMinutes)->timestamp,
        ]);

        try {
            Mail::to($user->email)->send(new LoginOtpMail($otp));
        } catch (\Throwable $e) {
            report($e);
        }

        return $otp;
    }

    /**
     * Resend an OTP code for the user currently stored in session.
     */
    public function resendLoginOtp(Request $request): ?Authenticatable
    {
        $userId = $request->session()->get('login_otp.id');
        if (!$userId) {
            return null;
        }

        $userModel = config('auth.providers.users.model', 'App\\Models\\User');
        $user = $userModel::find($userId);

        if (!$user) {
            return null;
        }

        $remember = $request->session()->get('login_otp.remember', false);
        $this->sendLoginOtp($user, $request, (bool) $remember);

        return $user;
    }

    /**
     * Verify the entered OTP code against session state.
     *
     * @throws ValidationException
     */
    public function verifyLoginOtp(Request $request, string $code): Authenticatable
    {
        $userId = $request->session()->get('login_otp.id');
        $storedCode = $request->session()->get('login_otp.code');
        $expiresAt = $request->session()->get('login_otp.expires_at');

        if (!$userId || !$storedCode) {
            throw ValidationException::withMessages([
                'code' => ['No pending login verification found. Please sign in again.'],
            ]);
        }

        if (now()->timestamp > (int) $expiresAt) {
            throw ValidationException::withMessages([
                'code' => ['The verification code has expired. Please request a new one.'],
            ]);
        }

        if (!hash_equals((string) $storedCode, (string) $code)) {
            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid.'],
            ]);
        }

        $userModel = config('auth.providers.users.model', 'App\\Models\\User');
        return $userModel::findOrFail($userId);
    }

    /**
     * Clean up OTP session state and log in the user.
     */
    public function completeEmailOtpLogin(Request $request, Authenticatable $user): bool
    {
        $remember = $request->session()->pull('login_otp.remember', false);
        $this->clearTwoFactorSessions($request);

        Auth::guard()->login($user, (bool) $remember);
        $request->session()->regenerate();

        return (bool) $remember;
    }

    // --------------------------------------------------------------------------
    // Authenticator App (TOTP)
    // --------------------------------------------------------------------------

    /**
     * Prepare Fortify's session state and dispatch the challenged event.
     */
    public function prepareAuthenticatorChallenge(Request $request, Authenticatable $user, bool $remember = false): void
    {
        $this->clearTwoFactorSessions($request);

        $request->session()->put([
            'login.id' => $user->getAuthIdentifier(),
            'login.remember' => $remember,
        ]);

        if (class_exists(TwoFactorAuthenticationChallenged::class)) {
            TwoFactorAuthenticationChallenged::dispatch($user);
        }
    }

    /**
     * Ensure a two-factor secret is generated for the user when setting up.
     */
    public function ensureAuthenticatorSecret(Authenticatable $user): void
    {
        if (is_null($user->two_factor_secret ?? null) && class_exists(EnableTwoFactorAuthentication::class)) {
            $enable = app(EnableTwoFactorAuthentication::class);
            $enable($user);
        }
    }

    /**
     * Get the user's authenticator configuration status.
     */
    public function getAuthenticatorStatus(Authenticatable $user): array
    {
        return [
            'twoFactorEnabled' => $this->userHasAuthenticator($user),
            'requiresConfirmation' => class_exists(Features::class) && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
        ];
    }

    // --------------------------------------------------------------------------
    // Passkeys (WebAuthn)
    // --------------------------------------------------------------------------

    /**
     * Get formatted list of passkeys for a user.
     */
    public function getUserPasskeys(Authenticatable $user): Collection
    {
        if (!Schema::hasTable('passkeys') || !method_exists($user, 'passkeys')) {
            return collect();
        }

        return $user->passkeys()
            ->select(['id', 'name', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn (Passkey $passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'created_at' => $passkey->created_at?->diffForHumans(),
                'created_at_formatted' => $passkey->created_at?->format('M d, Y h:i A'),
                'last_used_at' => $passkey->last_used_at?->diffForHumans() ?? 'Never',
                'last_used_at_formatted' => $passkey->last_used_at?->format('M d, Y h:i A') ?? 'Never',
            ]);
    }

    /**
     * Revoke / delete a user passkey.
     */
    public function deletePasskey(Authenticatable $user, Passkey $passkey): bool
    {
        if ($passkey->user_id !== $user->getAuthIdentifier()) {
            abort(403, 'Unauthorized action.');
        }

        return (bool) $passkey->delete();
    }

    // --------------------------------------------------------------------------
    // Session & Challenge Orchestration
    // --------------------------------------------------------------------------

    /**
     * Initiate multi-method challenge selection session.
     */
    public function initiateChallengeSelection(Request $request, int|string $userId, bool $remember = false): void
    {
        $request->session()->put([
            'login_challenge.id' => $userId,
            'login_challenge.remember' => $remember,
        ]);
    }

    /**
     * Clean all 2FA session keys across all methods.
     */
    public function clearTwoFactorSessions(Request $request): void
    {
        $request->session()->forget([
            'login_challenge.id',
            'login_challenge.remember',
            'login_otp.id',
            'login_otp.remember',
            'login_otp.code',
            'login_otp.expires_at',
            'login.id',
            'login.remember',
        ]);
    }

    /**
     * Transition a challenge selection to the selected method.
     *
     * @return string Target redirect URL
     */
    public function processChallengeSelection(Request $request, string $method): string
    {
        $userId = $request->session()->get('login_challenge.id')
            ?? $request->session()->get('login_otp.id')
            ?? $request->session()->get('login.id');

        if (!$userId) {
            return '/login';
        }

        $remember = $request->session()->get('login_challenge.remember')
            ?? $request->session()->get('login_otp.remember')
            ?? $request->session()->get('login.remember')
            ?? false;

        $userModel = config('auth.providers.users.model', 'App\\Models\\User');
        $user = $userModel::findOrFail($userId);

        if ($method === 'email') {
            $this->clearTwoFactorSessions($request);
            $this->sendLoginOtp($user, $request, (bool) $remember);
            return '/email-otp-login';
        }

        if ($method === 'authenticator') {
            $this->prepareAuthenticatorChallenge($request, $user, (bool) $remember);
            return '/two-factor-challenge';
        }

        return '/login';
    }

    /**
     * Render the appropriate response depending on the configured frontend stack (Inertia, Blade, or JSON API).
     */
    public function renderView(Request $request, string $pageKey, array $data = []): mixed
    {
        if ($request->wantsJson() || config('multi-2fa.stack') === 'api') {
            return response()->json($data);
        }

        $stack = config('multi-2fa.stack', 'auto');
        if ($stack === 'auto') {
            $hasInertia = class_exists(\Inertia\Inertia::class);
            $isInertiaRequest = $request->header('X-Inertia') || (!empty(config('multi-2fa.views.inertia')) && $hasInertia);
            $stack = ($hasInertia && $isInertiaRequest) ? 'inertia' : 'blade';
        }

        if ($stack === 'inertia' && class_exists(\Inertia\Inertia::class)) {
            $inertiaView = config("multi-2fa.views.inertia.{$pageKey}", "auth/{$pageKey}");
            return \Inertia\Inertia::render($inertiaView, $data);
        }

        $bladeView = config("multi-2fa.views.blade.{$pageKey}", "multi-2fa::{$pageKey}");
        return view($bladeView, $data);
    }
}
