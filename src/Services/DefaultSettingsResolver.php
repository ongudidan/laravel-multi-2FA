<?php

namespace Ongudidan\Multi2fa\Services;

use Ongudidan\Multi2fa\Contracts\SettingsResolverInterface;
use Illuminate\Support\Facades\Cache;

class DefaultSettingsResolver implements SettingsResolverInterface
{
    public const CACHE_KEY = 'security_settings';

    /**
     * Get all raw security settings.
     */
    public function getSettings(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $defaults = [
                'email_otp_enabled' => (bool) config('multi-2fa.features.email_otp', true),
                'two_factor_authenticator_enabled' => (bool) config('multi-2fa.features.authenticator', true),
                'passkey_enabled' => (bool) config('multi-2fa.features.passkey', true),
                'action_2fa_enabled' => false,
                'action_2fa_emails' => [],
                'action_2fa_features' => [],
                'auto_lock_enabled' => true,
                'auto_lock_minutes' => 5,
                'auto_logout_enabled' => true,
                'auto_logout_minutes' => 10,
                'logout_on_browser_close' => false,
                'attendance_lock_enabled' => false,
            ];

            // If an application has an Eloquent Setting model, merge from DB
            $settingModel = 'App\\Models\\Setting';
            if (class_exists($settingModel)) {
                try {
                    $setting = $settingModel::where('key', 'security_settings')->first();
                    if ($setting && !empty($setting->value)) {
                        $dbSettings = is_array($setting->value) 
                            ? $setting->value 
                            : (json_decode($setting->value, true) ?: []);
                        $defaults = array_replace_recursive($defaults, $dbSettings);
                    }
                } catch (\Throwable $e) {
                    // Fall back to defaults on unmigrated or unavailable table
                }
            }

            if (!empty($defaults['logout_on_browser_close'])) {
                config(['session.expire_on_close' => true]);
            }

            return $defaults;
        });
    }

    /**
     * Clear cached settings.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Check if Email OTP is enabled.
     */
    public function isEmailOtpEnabled(): bool
    {
        return (bool) ($this->getSettings()['email_otp_enabled'] ?? config('multi-2fa.features.email_otp', true));
    }

    /**
     * Check if Authenticator App (TOTP) is enabled.
     */
    public function isAuthenticatorEnabled(): bool
    {
        return (bool) ($this->getSettings()['two_factor_authenticator_enabled'] ?? config('multi-2fa.features.authenticator', true));
    }

    /**
     * Check if Passkeys are enabled.
     */
    public function isPasskeyEnabled(): bool
    {
        return (bool) ($this->getSettings()['passkey_enabled'] ?? config('multi-2fa.features.passkey', true));
    }
}
