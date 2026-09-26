<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // The breached-password check calls the HaveIBeenPwned range API (k-anonymity: only
        // the first 5 characters of the SHA-1 hash leave the server).
        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(12)->uncompromised()
            : Password::min(8)
        );

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('password.request', function (Request $request) {
            return Limit::perMinute(5)->by(
                Str::transliterate(Str::lower($request->string('email'))).'|'.$request->ip()
            );
        });

        RateLimiter::for('password.reset', function (Request $request) {
            return Limit::perMinute(5)->by(
                Str::transliterate(Str::lower($request->string('email'))).'|'.$request->ip()
            );
        });
    }
}
