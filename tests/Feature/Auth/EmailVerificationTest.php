<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unverified_account_is_sent_to_the_confirmation_page(): void
    {
        $user = User::factory()->owner()->unverified()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('admin.etablissements.index'))->assertRedirect(route('verification.notice'));

        $this->actingAs($user)->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Vérifiez votre boîte mail')
            ->assertSee($user->email);
    }

    public function test_the_signed_link_confirms_the_email(): void
    {
        $user = User::factory()->owner()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk();
    }

    public function test_an_invalid_or_foreign_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();

        $foreign = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $other->id,
            'hash' => sha1($other->email),
        ]);

        $this->actingAs($user)->get($foreign)->assertForbidden();
        $this->actingAs($user)->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_the_link_can_be_sent_again(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('success');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_the_confirmation_email_is_in_french(): void
    {
        $user = User::factory()->unverified()->create(['name' => 'Aya Kouassi', 'role' => UserRole::Client]);

        $mail = (new VerifyEmail)->toMail($user);

        $this->assertSame('Confirmez votre adresse email · DS HOLDING', $mail->subject);
        $this->assertSame('Bonjour Aya,', $mail->greeting);
        $this->assertSame('Confirmer mon adresse email', $mail->actionText);
    }
}
