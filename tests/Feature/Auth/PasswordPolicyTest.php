<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

/**
 * Password::defaults() is used by registration, password reset and password change.
 */
class PasswordPolicyTest extends TestCase
{
    public function test_production_requires_at_least_twelve_characters(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('')]);

        $this->assertFalse($this->passes('eleven-char'));
        $this->assertTrue($this->passes('twelve-chars'));
    }

    public function test_production_rejects_passwords_found_in_known_breaches(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $breachedPassword = 'correct-horse-battery';
        $hash = strtoupper(sha1($breachedPassword));
        Http::fake(['api.pwnedpasswords.com/range/'.substr($hash, 0, 5) => Http::response(substr($hash, 5).':4201')]);

        $this->assertFalse($this->passes($breachedPassword));
    }

    public function test_the_breach_check_only_sends_the_first_five_hash_characters(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('')]);

        $this->passes('a-long-unbreached-passphrase');

        $prefix = substr(strtoupper(sha1('a-long-unbreached-passphrase')), 0, 5);
        Http::assertSent(fn ($request) => $request->url() === "https://api.pwnedpasswords.com/range/{$prefix}");
    }

    public function test_non_production_environments_keep_the_eight_character_minimum(): void
    {
        Http::fake();

        $this->assertTrue($this->passes('password'));
        $this->assertFalse($this->passes('short'));
        Http::assertNothingSent();
    }

    private function passes(string $password): bool
    {
        return Validator::make(['password' => $password], ['password' => Password::defaults()])->passes();
    }
}
