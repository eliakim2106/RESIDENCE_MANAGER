<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Mockery;
use Tests\TestCase;

/**
 * Connexion des clients avec Google / Facebook : création du compte, rattachement, refus des comptes de gestion.
 */
class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google.client_id' => 'id-test', 'services.google.client_secret' => 'secret-test']);
    }

    private function fakeGoogleUser(string $email, string $id = 'g-123', string $name = 'Awa Koné'): void
    {
        $social = (new SocialUser)->map(['id' => $id, 'name' => $name, 'email' => $email, 'avatar' => 'https://example.com/a.jpg']);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($social);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_buttons_appear_only_for_configured_providers(): void
    {
        $this->get(route('login'))->assertSee('Se connecter avec Google')->assertDontSee('avec Facebook');

        config(['services.google.client_id' => null]);
        $this->get(route('login'))->assertDontSee('avec Google');
        $this->get(route('social.redirect', 'google'))->assertRedirect(route('login'))->assertSessionHas('error');
    }

    public function test_new_visitor_gets_a_client_account_and_completes_its_profile(): void
    {
        $this->fakeGoogleUser('awa@example.com');

        $this->get(route('social.callback', 'google'))->assertRedirect(route('client.profile.complete'));

        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserRole::Client, $user->role);
        $this->assertSame('g-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->needsProfileCompletion());
    }

    public function test_existing_client_is_linked_by_email(): void
    {
        $client = User::factory()->create(['email' => 'awa@example.com']);
        $this->fakeGoogleUser('AWA@example.com');

        $this->get(route('social.callback', 'google'))->assertRedirect(route('client.dashboard'));

        $this->assertAuthenticatedAs($client);
        $this->assertSame('g-123', $client->fresh()->google_id);
        $this->assertSame(1, User::count());
    }

    public function test_management_accounts_must_use_their_password(): void
    {
        User::factory()->owner()->create(['email' => 'owner@example.com']);
        $this->fakeGoogleUser('owner@example.com');

        $this->get(route('social.callback', 'google'))->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
    }
}
