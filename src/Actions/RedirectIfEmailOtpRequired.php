<?php

namespace Ongudidan\Multi2fa\Actions;

use Ongudidan\Multi2fa\Services\TwoFactorService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

class RedirectIfEmailOtpRequired
{
    /**
     * The guard implementation.
     *
     * @var \Illuminate\Contracts\Auth\StatefulGuard|\Illuminate\Auth\SessionGuard
     */
    protected $guard;

    /**
     * The login rate limiter instance.
     *
     * @var \Laravel\Fortify\LoginRateLimiter
     */
    protected $limiter;

    /**
     * The two-factor authentication service.
     *
     * @var \Ongudidan\Multi2fa\Services\TwoFactorService
     */
    protected $twoFactorService;

    /**
     * Create a new action instance.
     */
    public function __construct(StatefulGuard $guard, LoginRateLimiter $limiter, TwoFactorService $twoFactorService)
    {
        $this->guard = $guard;
        $this->limiter = $limiter;
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Handle the incoming request.
     */
    public function handle($request, $next)
    {
        $emailOtpEnabled = $this->twoFactorService->isEmailOtpEnabled();
        $twoFactorEnabledGlobally = $this->twoFactorService->isAuthenticatorEnabled();
        $passkeyEnabledGlobally = $this->twoFactorService->isPasskeyEnabled();

        // If no 2FA methods are enabled globally, proceed with standard login
        if (!$emailOtpEnabled && !$twoFactorEnabledGlobally && !$passkeyEnabledGlobally) {
            return $next($request);
        }

        // Validate user's credentials early so we can check user configurations
        $user = $this->validateCredentials($request);
        if (!$user) {
            return $next($request);
        }

        $availableMethods = $this->twoFactorService->getAvailableMethodsForUser($user);

        // If user has multiple 2FA methods available, redirect to method selection screen
        if (count($availableMethods) > 1) {
            $this->twoFactorService->initiateChallengeSelection($request, $user->getAuthIdentifier(), $request->boolean('remember'));

            return $request->wantsJson()
                ? response()->json(['login_challenge_required' => true])
                : redirect('/login-challenge-selection');
        }

        // If ONLY Email OTP is available for this user, send code and redirect to entry page
        if ($availableMethods === ['email']) {
            $this->twoFactorService->sendLoginOtp($user, $request, $request->boolean('remember'));

            return $request->wantsJson()
                ? response()->json(['email_otp_required' => true])
                : redirect('/email-otp-login');
        }

        // If ONLY Passkey is enrolled for this user, redirect to challenge selection
        if ($availableMethods === ['passkey']) {
            $this->twoFactorService->initiateChallengeSelection($request, $user->getAuthIdentifier(), $request->boolean('remember'));

            return $request->wantsJson()
                ? response()->json(['login_challenge_required' => true])
                : redirect('/login-challenge-selection');
        }

        // Otherwise (only authenticator, or none), proceed down the Fortify pipeline
        return $next($request);
    }

    /**
     * Attempt to validate the incoming credentials.
     */
    protected function validateCredentials($request)
    {
        if (Fortify::$authenticateUsingCallback) {
            return tap(call_user_func(Fortify::$authenticateUsingCallback, $request), function ($user) use ($request) {
                if (! $user) {
                    $this->fireFailedEvent($request);
                    $this->throwFailedAuthenticationException($request);
                }
            });
        }

        $provider = $this->guard->getProvider();

        return tap($provider->retrieveByCredentials($request->only(Fortify::username(), 'password')), function ($user) use ($provider, $request) {
            if (! $user || ! $provider->validateCredentials($user, ['password' => $request->password])) {
                $this->fireFailedEvent($request, $user);
                $this->throwFailedAuthenticationException($request);
            }

            if (config('hashing.rehash_on_login', true) && method_exists($provider, 'rehashPasswordIfRequired')) {
                $provider->rehashPasswordIfRequired($user, ['password' => $request->password]);
            }
        });
    }

    /**
     * Throw a failed authentication validation exception.
     */
    protected function throwFailedAuthenticationException($request)
    {
        $this->limiter->increment($request);

        throw ValidationException::withMessages([
            Fortify::username() => [trans('auth.failed')],
        ]);
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
     */
    protected function fireFailedEvent($request, $user = null)
    {
        event(new Failed($this->guard?->name ?? config('fortify.guard'), $user, [
            Fortify::username() => $request->{Fortify::username()},
            'password' => $request->password,
        ]));
    }
}
