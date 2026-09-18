<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Marketing\Contracts\SmsGatewayInterface;

class CustomerOtpService
{
    private const TTL_MINUTES = 10;
    private const COOLDOWN_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;

    public function __construct(private SmsGatewayInterface $gateway) {}

    /**
     * Reduce a phone to digits only so one number maps to one cache key.
     */
    public function normalize(string $phone): string
    {
        return preg_replace('/\D/', '', trim($phone)) ?? '';
    }

    /**
     * Generate, store, and SMS a 6-digit OTP. Honors a per-phone resend cooldown.
     *
     * The $purpose scopes the cache keys so independent journeys (registration,
     * password reset) never collide. Default 'reg' preserves the original keys.
     *
     * @return array{ok:bool,reason?:string,retry_after?:int}
     */
    public function send(string $phone, string $purpose = 'reg'): array
    {
        $key = $this->normalize($phone);

        if (Cache::has($this->cooldownKey($key, $purpose))) {
            return ['ok' => false, 'reason' => 'cooldown', 'retry_after' => self::COOLDOWN_SECONDS];
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->otpKey($key, $purpose), $otp, now()->addMinutes(self::TTL_MINUTES));
        Cache::put($this->attemptsKey($key, $purpose), 0, now()->addMinutes(self::TTL_MINUTES));

        $store = config('app.name');
        $message = "Your {$store} verification code is {$otp}. Valid for " . self::TTL_MINUTES . " minutes.";

        if (! $this->gateway->send($phone, $message)) {
            // Failure: drop the code and skip the cooldown so the user can retry.
            Cache::forget($this->otpKey($key, $purpose));
            Log::warning('Customer OTP send failed', ['phone' => $key, 'purpose' => $purpose]);

            return ['ok' => false, 'reason' => 'send_failed'];
        }

        Cache::put($this->cooldownKey($key, $purpose), 1, now()->addSeconds(self::COOLDOWN_SECONDS));

        return ['ok' => true];
    }

    /**
     * Verify a submitted code; caps attempts and consumes the code on success.
     */
    public function verify(string $phone, string $code, string $purpose = 'reg'): bool
    {
        $key = $this->normalize($phone);
        $stored = Cache::get($this->otpKey($key, $purpose));

        if (! $stored) {
            return false;
        }

        $attempts = (int) Cache::get($this->attemptsKey($key, $purpose), 0) + 1;
        Cache::put($this->attemptsKey($key, $purpose), $attempts, now()->addMinutes(self::TTL_MINUTES));

        if ($attempts > self::MAX_ATTEMPTS) {
            Cache::forget($this->otpKey($key, $purpose));

            return false;
        }

        if (hash_equals((string) $stored, $code)) {
            Cache::forget($this->otpKey($key, $purpose));
            Cache::forget($this->attemptsKey($key, $purpose));

            return true;
        }

        return false;
    }

    private function otpKey(string $key, string $purpose): string
    {
        return "customer_{$purpose}_otp_{$key}";
    }

    private function attemptsKey(string $key, string $purpose): string
    {
        return "customer_{$purpose}_otp_attempts_{$key}";
    }

    private function cooldownKey(string $key, string $purpose): string
    {
        return "customer_{$purpose}_otp_cooldown_{$key}";
    }
}
