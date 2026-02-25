<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
