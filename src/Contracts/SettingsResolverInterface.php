<?php

namespace Ongudidan\Multi2fa\Contracts;

interface SettingsResolverInterface
{
    /**
     * Check if Email OTP is enabled.
     */
    public function isEmailOtpEnabled(): bool;

    /**
     * Check if Authenticator App (TOTP) is enabled.
     */
    public function isAuthenticatorEnabled(): bool;

    /**
     * Check if Passkeys are enabled.
     */
    public function isPasskeyEnabled(): bool;

    /**
     * Get all raw security settings.
     */
    public function getSettings(): array;

    /**
     * Clear cached settings.
     */
    public function clearCache(): void;
}
