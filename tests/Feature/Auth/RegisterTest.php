<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'nom' => 'Kouassi',
            'prenoms' => 'Aya Marie',
            'country_code' => '+225',
            'telephone' => '07 01 02 03 04',
            'email' => 'Aya.Kouassi@Example.com',
            'pays' => "Côte d'Ivoire",
            'ville' => 'Abidjan',
            'password' => 'motdepasse1',
            'confirm_password' => 'motdepasse1',
            'terms' => '1',
            ...$overrides,
        ];
    }

    public function test_client_can_register_and_must_confirm_their_email(): void
    {
        Notification::fake();

        $this->post(route('register.client'), $this->payload())
            ->assertRedirect(route('verification.notice'));

        $user = User::firstWhere('email', 'aya.kouassi@example.com');

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->assertSame('Aya Marie Kouassi', $user->name);
        $this->assertSame(UserRole::Client, $user->role);
        $this->assertSame('+225 0701020304', $user->phone);
        $this->assertSame('Abidjan', $user->city);
        $this->assertSame("Côte d'Ivoire", $user->country);
    }

    public function test_owner_can_register(): void
    {
        $this->post(route('register.owner'), $this->payload())->assertRedirect(route('verification.notice'));

        $this->assertSame(UserRole::Owner, User::firstWhere('email', 'aya.kouassi@example.com')->role);
    }

    public function test_email_and_phone_must_be_unique(): void
    {
        User::factory()->create(['email' => 'aya.kouassi@example.com']);

        $this->post(route('register.client'), $this->payload())->assertSessionHasErrors('email');

        User::factory()->create(['phone' => '+225 0701020304']);

        $this->post(route('register.client'), $this->payload(['email' => 'autre@example.com']))
            ->assertSessionHasErrors(['telephone' => 'Ce numéro de téléphone est déjà utilisé.']);
    }

    public function test_passwords_must_match_and_terms_be_accepted(): void
    {
        $this->post(route('register.client'), $this->payload(['confirm_password' => 'autre', 'terms' => null]))
            ->assertSessionHasErrors(['confirm_password', 'terms']);

        $this->assertDatabaseCount('users', 0);
    }
}
