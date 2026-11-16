<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\InviteUserToCompleteRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationCompletionInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_invitation_opens_the_completion_form_without_exposing_the_verification_code(): void
    {
        $user = $this->pendingUser();

        $this->get($this->invitationUrl($user))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/CompleteRegistration')
                ->where('email', $user->email)
                ->where('name', $user->name)
                ->missing('token'));
    }

    public function test_an_invalid_invitation_signature_is_rejected(): void
    {
        $this->get(route('registration.complete', $this->pendingUser()))->assertForbidden();
    }

    public function test_a_valid_signed_post_completes_the_pending_registration_and_logs_the_user_in(): void
    {
        $user = $this->pendingUser();

        $this->post($this->invitationUrl($user), $this->completionData())
            ->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->verification_code);
        $this->assertNull($user->verification_sent_at);
    }

    public function test_an_unsigned_post_cannot_complete_registration(): void
    {
        $this->post(route('registration.store-completion', $this->pendingUser()), $this->completionData())
            ->assertForbidden();
    }

    public function test_an_expired_invitation_cannot_complete_registration(): void
    {
        $user = $this->pendingUser();

        $this->post($this->invitationUrl($user, now()->subMinute()), $this->completionData())
            ->assertForbidden();
    }

    public function test_a_signed_invitation_cannot_be_used_for_another_user(): void
    {
        $invitedUser = $this->pendingUser();
        $otherUser = $this->pendingUser();
        $tamperedUrl = str_replace(
            '/'.$invitedUser->id.'?',
            '/'.$otherUser->id.'?',
            $this->invitationUrl($invitedUser),
        );

        $this->post($tamperedUrl, $this->completionData())->assertForbidden();
    }

    public function test_an_invitation_cannot_be_reused_after_completion(): void
    {
        $user = $this->pendingUser();
        $url = $this->invitationUrl($user);

        $this->post($url, $this->completionData())->assertRedirect(route('dashboard'));
        $this->post($url, $this->completionData())->assertForbidden();
    }

    public function test_an_already_completed_user_cannot_use_a_signed_invitation(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->post($this->invitationUrl($user), $this->completionData())->assertForbidden();
    }

    public function test_the_existing_notification_generates_a_temporary_signed_completion_url(): void
    {
        Notification::fake();
        $user = $this->pendingUser();

        $user->notify(new InviteUserToCompleteRegistration());

        Notification::assertSentTo($user, InviteUserToCompleteRegistration::class, function ($notification, $channels) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return $channels === ['mail']
                && str_contains($url, '/register/complete/'.$user->id)
                && str_contains($url, 'expires=')
                && str_contains($url, 'signature=');
        });
    }

    public function test_admin_users_creation_preserves_a_pending_invitation_state(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Synthetic Pending User',
                'email' => 'synthetic-pending@example.test',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'synthetic-pending@example.test')->firstOrFail();

        $this->assertFalse($user->is_active);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->verification_code);
        $this->assertNotNull($user->verification_sent_at);
        Notification::assertSentTo($user, InviteUserToCompleteRegistration::class);
    }

    private function pendingUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => null,
            'is_active' => false,
            'verification_code' => '1234',
            'verification_sent_at' => now(),
        ]);
    }

    private function invitationUrl(User $user, $expiration = null): string
    {
        return URL::temporarySignedRoute(
            'registration.complete',
            $expiration ?? now()->addHours(48),
            ['user' => $user->id],
        );
    }

    private function completionData(): array
    {
        return [
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone_number' => '11999999999',
            'birth_date' => '1990-01-01',
        ];
    }
}
