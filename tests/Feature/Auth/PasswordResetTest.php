<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Mot de passe oublié : lien par email (sans révéler qui est inscrit), nouveau mot de passe, lien expiré.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_the_forgotten_password_page(): void
    {
        $this->get(route('login'))->assertSee(route('password.request'), false);
        $this->get(route('password.request'))->assertOk()->assertSee('Mot de passe oublié ?');
    }

    public function test_a_link_is_sent_without_revealing_whether_the_account_exists(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'awa@example.com']);

        $this->post(route('password.email'), ['email' => 'AWA@example.com'])->assertSessionHas('success');
        $knownMessage = session('success');
        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
            $mail = $notification->toMail($user);

            return str_contains($mail->actionUrl, '/reinitialiser-mot-de-passe/') && $mail->subject === 'Réinitialisation de votre mot de passe DS HOLDING';
        });

        $unknown = $this->post(route('password.email'), ['email' => 'inconnu@example.com'])->assertSessionHas('success');
        $this->assertSame(
            str_replace('awa@example.com', '', $knownMessage),
            str_replace('inconnu@example.com', '', $unknown->getSession()->get('success')),
            'Même message pour un compte inconnu',
        );
        Notification::assertSentTimes(ResetPasswordNotification::class, 1);

        // Nouvelle demande immédiate : limitée
        $this->post(route('password.email'), ['email' => 'awa@example.com'])->assertSessionHas('error');
    }

    public function test_the_link_lets_the_user_choose_a_new_password(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'awa@example.com']);
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk()->assertSee('Nouveau mot de passe');

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NouveauPass2026',
            'password_confirmation' => 'NouveauPass2026',
        ])->assertRedirect(route('login'))->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NouveauPass2026', $user->password));
        $this->assertNotNull($user->email_verified_at, 'Le lien reçu par email confirme l’adresse');

        // Le lien ne sert qu'une fois
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'EncoreUn2026',
            'password_confirmation' => 'EncoreUn2026',
        ])->assertSessionHasErrors(['email' => 'Ce lien n’est plus valable : il a expiré ou a déjà servi. Demandez-en un nouveau.']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'NouveauPass2026'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_passwords_must_match_and_be_strong_enough(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.update'), ['token' => $token, 'email' => $user->email, 'password' => 'court', 'password_confirmation' => 'court'])
            ->assertSessionHasErrors('password');
        $this->post(route('password.update'), ['token' => $token, 'email' => $user->email, 'password' => 'NouveauPass2026', 'password_confirmation' => 'Autre2026xx'])
            ->assertSessionHasErrors(['password' => 'Les deux mots de passe ne correspondent pas.']);
    }
}
