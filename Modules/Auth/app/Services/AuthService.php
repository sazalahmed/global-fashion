<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthService
{
    /**
     * Attempt to log in a user with the given credentials.
     */
    public function login(array $credentials, bool $remember = false): bool
    {
        return Auth::attempt($credentials, $remember);
    }

    /**
     * Log out the current user, invalidate session, and regenerate token.
     */
    public function logout(): void
    {
        Auth::logout();

        if ($session = request()->session()) {
            $session->invalidate();
            $session->regenerateToken();
        }
    }

    /**
     * Generate a 6-digit OTP for the given email and store it in cache for 10 minutes.
     * Returns true on success (actual SMS/email sending is a placeholder).
     */
    public function sendOtp(string $email): bool
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put("otp_{$email}", $otp, now()->addMinutes(10));

        // TODO: Send OTP via SMS or email

        return true;
    }

    /**
     * Verify the OTP for the given email against the cached value.
     */
    public function verifyOtp(string $email, string $otp): bool
    {
        $cachedOtp = Cache::get("otp_{$email}");

        if ($cachedOtp && $cachedOtp === $otp) {
            Cache::forget("otp_{$email}");
            return true;
        }

        return false;
    }

    /**
     * Reset the user's password using the Password broker.
     */
    public function resetPassword(string $email, string $token, string $password): bool
    {
        $status = Password::reset(
            [
                'email'                 => $email,
                'token'                 => $token,
                'password'              => $password,
                'password_confirmation' => $password,
            ],
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET;
    }
}
