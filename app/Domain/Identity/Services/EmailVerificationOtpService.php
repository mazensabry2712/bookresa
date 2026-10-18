<?php

namespace App\Domain\Identity\Services;

use App\Models\User;
use App\Notifications\Auth\EmailVerificationOtpNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class EmailVerificationOtpService
{
    public const int OTP_TTL_MINUTES = 10;

    private const int RESEND_COOLDOWN_SECONDS = 60;

    private const int MAX_ATTEMPTS = 5;

    /**
     * Send a short-lived email verification code.
     */
    public function send(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $cooldownKey = $this->key($user, 'cooldown');

        if (! Cache::add($cooldownKey, true, now()->addSeconds(self::RESEND_COOLDOWN_SECONDS))) {
            return false;
        }

        $code = (string) random_int(100000, 999999);
        $codeKey = $this->key($user, 'code');

        Cache::put($codeKey, $this->digest($user, $code), now()->addMinutes(self::OTP_TTL_MINUTES));
        RateLimiter::clear($this->key($user, 'attempts'));

        try {
            $user->notify(new EmailVerificationOtpNotification($code));
        } catch (Throwable $exception) {
            Cache::forget($codeKey);
            Cache::forget($cooldownKey);

            throw $exception;
        }

        return true;
    }

    /**
     * Validate a submitted code without storing it in plain text.
     */
    public function validateCode(User $user, string $code): void
    {
        $attemptKey = $this->key($user, 'attempts');

        if (RateLimiter::tooManyAttempts($attemptKey, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'code' => __('app.verification_code_too_many_attempts'),
            ]);
        }

        $storedDigest = Cache::get($this->key($user, 'code'));

        if (! is_string($storedDigest)) {
            throw ValidationException::withMessages([
                'code' => __('app.verification_code_expired'),
            ]);
        }

        if (! hash_equals($storedDigest, $this->digest($user, $code))) {
            RateLimiter::hit($attemptKey, self::OTP_TTL_MINUTES * 60);

            if (RateLimiter::attempts($attemptKey) >= self::MAX_ATTEMPTS) {
                throw ValidationException::withMessages([
                    'code' => __('app.verification_code_too_many_attempts'),
                ]);
            }

            throw ValidationException::withMessages([
                'code' => __('app.verification_code_invalid'),
            ]);
        }
    }

    /**
     * Remove OTP state after successful verification.
     */
    public function forget(User $user): void
    {
        Cache::forget($this->key($user, 'code'));
        Cache::forget($this->key($user, 'cooldown'));
        RateLimiter::clear($this->key($user, 'attempts'));
    }

    private function digest(User $user, string $code): string
    {
        $value = implode('|', [
            $user->getKey(),
            Str::lower($user->getEmailForVerification()),
            $code,
        ]);

        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    private function key(User $user, string $purpose): string
    {
        $emailHash = hash('sha256', Str::lower($user->getEmailForVerification()));

        return 'identity:email-verification:'.$user->getKey().':'.$emailHash.':'.$purpose;
    }
}
