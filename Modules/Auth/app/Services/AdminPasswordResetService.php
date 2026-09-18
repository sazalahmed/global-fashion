<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Marketing\Contracts\SmsGatewayInterface;
use Modules\Security\Services\TwoFactorService;

class AdminPasswordResetService
{
    private const OTP_TTL_MINUTES = 10;
    private const COOLDOWN_SECONDS = 60;

    public function __construct(
        private SmsGatewayInterface $sms,
        private TwoFactorService $twoFactor,
    ) {}

    /**
     * Decide how this email proves identity, and (for SMS) send the code.
     *
     * @return array{method:string, maskedPhone?:string, sent?:bool, reason?:string, retry_after?:int}
     */
    public function initiate(string $email): array
    {
        $user = User::where('email', $email)->first();

        // Never reveal whether an account exists.
        if (! $user) {
            return ['method' => 'generic'];
        }

        if ($user->hasTwoFactorEnabled()) {
            return ['method' => 'totp'];
        }

        if (empty($user->phone)) {
            return ['method' => 'unavailable'];
        }

        if (Cache::has($this->cooldownKey($email))) {
            return [
                'method'      => 'sms',
                'sent'        => false,
                'reason'      => 'cooldown',
                'retry_after' => self::COOLDOWN_SECONDS,
            ];
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Persist the hashed OTP exactly like the legacy flow (keyed by email).
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($otp), 'created_at' => now()],
        );
        // Keep the plaintext briefly so local QA can read it; never logged.
        Cache::put($this->otpCacheKey($email), $otp, now()->addMinutes(self::OTP_TTL_MINUTES));

        $store   = config('app.name');
        $message = "Your {$store} password reset code is {$otp}. Valid for " . self::OTP_TTL_MINUTES . " minutes.";

        if (! $this->sms->send($user->phone, $message)) {
            Cache::forget($this->otpCacheKey($email));
            Log::warning('Admin password reset SMS failed', ['email' => $email]);

            return ['method' => 'sms', 'sent' => false, 'reason' => 'send_failed'];
        }

        Cache::put($this->cooldownKey($email), 1, now()->addSeconds(self::COOLDOWN_SECONDS));

        return ['method' => 'sms', 'maskedPhone' => $this->maskPhone($user->phone)];
    }

    /**
     * Verify a code (TOTP if enrolled, else the SMS OTP). On success, issue a
     * one-time reset token (stored hashed) and return its plaintext value.
     */
    public function verify(string $email, string $code): ?string
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            return null;
        }

        $ok = $user->hasTwoFactorEnabled()
            ? $this->twoFactor->verify($user, $code)
            : $this->verifySmsOtp($email, $code);

        if (! $ok) {
            return null;
        }

        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()],
        );
        Cache::forget($this->otpCacheKey($email));

        return $token;
    }

    private function verifySmsOtp(string $email, string $code): bool
    {
        $row = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (! $row) {
            return false;
        }
        if (now()->diffInMinutes($row->created_at) > self::OTP_TTL_MINUTES) {
            return false;
        }

        return Hash::check($code, $row->token);
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (strlen($digits) <= 4) {
            return $digits;
        }

        return substr($digits, 0, 2) . str_repeat('X', strlen($digits) - 4) . substr($digits, -2);
    }

    private function otpCacheKey(string $email): string
    {
        return 'admin_pwd_otp_' . $email;
    }

    private function cooldownKey(string $email): string
    {
        return 'admin_pwd_otp_cooldown_' . $email;
    }
}
