<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS_PER_IP = 5;

    private const MAX_ATTEMPTS_PER_EMAIL = 20;

    private const EMAIL_DECAY_SECONDS = 15 * 60;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->emailThrottleKey(), self::EMAIL_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->emailThrottleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * Two limits apply: 5 attempts per email+IP (a single guesser), and a per-email ceiling
     * that also stops guessing spread across many IP addresses.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $limitedKey = collect([
            $this->throttleKey() => self::MAX_ATTEMPTS_PER_IP,
            $this->emailThrottleKey() => self::MAX_ATTEMPTS_PER_EMAIL,
        ])->filter(fn (int $maxAttempts, string $key) => RateLimiter::tooManyAttempts($key, $maxAttempts))->keys()->first();

        if ($limitedKey === null) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($limitedKey);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    /**
     * The rate limiting key shared by every IP address trying this email.
     */
    public function emailThrottleKey(): string
    {
        return 'login-email:'.Str::transliterate(Str::lower($this->string('email')));
    }
}
