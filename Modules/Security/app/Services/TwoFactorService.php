<?php

namespace Modules\Security\Services;

use App\Models\User;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorService
{
    /** Accept the previous/next 30s window to tolerate clock drift. */
    private const WINDOW = 1;

    public function __construct(private Google2FA $google2fa) {}

    /**
     * Generate a fresh secret, store it UNCONFIRMED, and return the secret +
     * an inline QR image for the authenticator app.
     *
     * @return array{secret:string, qr:string}
     */
    public function generateSecret(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        $user->forceFill([
            'two_factor_secret'       => $secret,
            'two_factor_confirmed_at' => null,
        ])->save();

        $qr = $this->google2fa->getQRCodeInline(
            (string) config('app.name'),
            $user->email,
            $secret
        );

        return ['secret' => $secret, 'qr' => $qr];
    }

    /**
     * Verify a code and, on success, mark enrollment as confirmed.
     */
    public function confirm(User $user, string $code): bool
    {
        if (! $this->verify($user, $code)) {
            return false;
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return true;
    }

    /**
     * Verify a TOTP code against the user's stored secret (±1 window).
     */
    public function verify(User $user, string $code): bool
    {
        if (empty($user->two_factor_secret)) {
            return false;
        }

        return (bool) $this->google2fa->verifyKey($user->two_factor_secret, $code, self::WINDOW);
    }

    /**
     * Remove 2FA entirely from the user.
     */
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret'       => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }
}
