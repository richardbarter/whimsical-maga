<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_link_request_response_is_identical_for_known_and_unknown_emails(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $knownResponse = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);
        $unknownResponse = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'nobody@example.com']);

        $knownResponse->assertSessionHasNoErrors()->assertRedirect('/forgot-password');
        $unknownResponse->assertSessionHasNoErrors()->assertRedirect('/forgot-password');
        $this->assertSame($knownResponse->getSession()->get('status'), $unknownResponse->getSession()->get('status'));
    }

    public function test_repeated_link_requests_do_not_reveal_that_the_account_exists(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);
        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors();
    }

    public function test_reset_errors_do_not_reveal_whether_the_email_has_an_account(): void
    {
        $user = User::factory()->create();

        $payload = ['token' => 'not-a-real-token', 'password' => 'password', 'password_confirmation' => 'password'];

        $known = $this->post('/reset-password', [...$payload, 'email' => $user->email]);
        $unknown = $this->post('/reset-password', [...$payload, 'email' => 'nobody@example.com']);

        $this->assertSame(
            $known->getSession()->get('errors')->first('email'),
            $unknown->getSession()->get('errors')->first('email'),
        );
    }
}
