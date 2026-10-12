<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication Methods
    |--------------------------------------------------------------------------
    |
    | Supported: 'passkey', 'authenticator', 'email'
    | You can enable or disable methods globally here or rely on the dynamic
    | settings resolver.
    |
    */
    'features' => [
        'email_otp' => true,
        'authenticator' => true,
        'passkey' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend Stack
    |--------------------------------------------------------------------------
    |
    | Defines how authentication pages are rendered:
    | - 'auto': Detects Inertia.js if installed, otherwise falls back to Blade.
    | - 'inertia': Renders Inertia Vue/React pages.
    | - 'blade': Renders Blade views.
    | - 'api': Headless JSON responses (ideal for Mobile apps / React Native / SPAs).
    |
    */
    'stack' => 'auto',

    /*
    |--------------------------------------------------------------------------
    | Settings Resolver
    |--------------------------------------------------------------------------
    |
    | The class used to resolve whether 2FA features are enabled at runtime.
    | By default, it checks the database `settings` table (key: `security_settings`)
    | and falls back to the static `features` config above.
    |
    */
    'settings_resolver' => \Ongudidan\Multi2fa\Services\DefaultSettingsResolver::class,

    /*
    |--------------------------------------------------------------------------
    | OTP Code Expiry
    |--------------------------------------------------------------------------
    |
    | Number of minutes an email OTP verification code remains valid.
    |
    */
    'otp_expiry_minutes' => 10,

    /*
    |--------------------------------------------------------------------------
    | Route Configurations
    |--------------------------------------------------------------------------
    |
    | Enable or disable the built-in routes for challenge selection, email OTP,
    | two-factor settings, and passkey settings.
    |
    */
    'routes' => [
        'enabled' => true,
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | View Mappings (Inertia & Blade)
    |--------------------------------------------------------------------------
    |
    | Customize the view components rendered for each frontend stack.
    |
    */
    'views' => [
        'inertia' => [
            'email_otp' => 'auth/EmailOtpLogin',
            'login_challenge' => 'auth/LoginChallengeSelection',
            'two_factor_settings' => 'settings/TwoFactor',
            'passkeys_settings' => 'settings/Passkeys',
        ],
        'blade' => [
            'email_otp' => 'multi-2fa::auth.email-otp',
            'login_challenge' => 'multi-2fa::auth.challenge-selection',
            'two_factor_settings' => 'multi-2fa::settings.two-factor',
            'passkeys_settings' => 'multi-2fa::settings.passkeys',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirect Destination
    |--------------------------------------------------------------------------
    |
    | Where to redirect authenticated users after completing 2FA verification.
    | If null, will fallback to config('fortify.home') or '/dashboard'.
    |
    */
    'home' => null,
];
