<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_and_is_sent_to_the_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('login_logs', ['user_id' => $user->id, 'successful' => true]);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))
            ->post(route('login'), ['email' => $user->email, 'password' => 'mauvais-mot-de-passe'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Adresse email ou mot de passe incorrect.']);

        $this->assertGuest();
    }

    public function test_suspended_account_cannot_log_in(): void
    {
        $user = User::factory()->suspended()->create(['password' => 'password123']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password123'])
            ->assertSessionHasErrors(['email' => 'Votre compte est actuellement indisponible.']);

        $this->assertGuest();
    }

    public function test_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
