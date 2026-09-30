<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Utilisateurs (administrateurs), journal des connexions et « Mon profil » (tous les rôles).
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | UTILISATEURS
    |--------------------------------------------------------------------------
    */

    public function test_user_list_is_reserved_to_administrators_and_filters_by_role(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->create(['name' => 'Awa Client']);
        $owner = User::factory()->owner()->create(['name' => 'Yao Propriétaire']);

        $this->actingAs($admin)->get(route('admin.utilisateurs.index'))
            ->assertOk()->assertSee('Awa Client')->assertSee('Yao Propriétaire');

        $this->actingAs($admin)->get(route('admin.utilisateurs.index', ['statut' => 'proprietaires']))
            ->assertSee('Yao Propriétaire')->assertDontSee('Awa Client');

        $this->actingAs($admin)->get(route('admin.utilisateurs.show', $client))->assertOk()->assertSee('Suspendre le compte');

        $this->actingAs($owner)->get(route('admin.utilisateurs.index'))->assertForbidden();
        $this->actingAs($client)->get(route('admin.utilisateurs.show', $owner))->assertForbidden();
    }

    public function test_admin_suspends_and_reactivates_an_account(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.utilisateurs.suspend', $client))->assertSessionHas('success');
        $this->assertSame(UserStatus::Suspended, $client->fresh()->statut);

        // Le compte suspendu est déconnecté à sa requête suivante
        $this->actingAs($client->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->actingAs($admin)->patch(route('admin.utilisateurs.reactivate', $client))->assertSessionHas('success');
        $this->assertSame(UserStatus::Active, $client->fresh()->statut);
    }

    public function test_admins_cannot_act_on_themselves_or_on_other_administrators(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.utilisateurs.suspend', $admin))->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.utilisateurs.suspend', $otherAdmin))->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.utilisateurs.role', $otherAdmin), ['role' => 'client'])->assertForbidden();

        $this->assertSame(UserStatus::Active, $otherAdmin->fresh()->statut);
    }

    public function test_only_a_super_admin_changes_roles(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $client = User::factory()->create();

        $this->actingAs($superAdmin)->patch(route('admin.utilisateurs.role', $client), ['role' => 'owner'])->assertSessionHas('success');
        $this->assertSame(UserRole::Owner, $client->fresh()->role);

        $this->actingAs($superAdmin)->patch(route('admin.utilisateurs.role', $client), ['role' => 'pirate'])->assertSessionHasErrors('role');
        $this->actingAs($superAdmin)->patch(route('admin.utilisateurs.role', $superAdmin), ['role' => 'client'])->assertForbidden();
    }

    public function test_login_journal_lists_failed_attempts(): void
    {
        $admin = User::factory()->admin()->create();
        LoginLog::create(['email' => 'intrus@example.com', 'ip_address' => '10.0.0.9', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0', 'successful' => false]);
        LoginLog::create(['user_id' => $admin->id, 'email' => $admin->email, 'ip_address' => '10.0.0.1', 'successful' => true]);

        $this->actingAs($admin)->get(route('admin.utilisateurs.connexions', ['statut' => 'echouees']))
            ->assertOk()
            ->assertSee('intrus@example.com')
            ->assertSee('Chrome · Windows')
            ->assertSee('1 tentative échouée')
            ->assertDontSee('10.0.0.1');
    }

    /*
    |--------------------------------------------------------------------------
    | MON PROFIL
    |--------------------------------------------------------------------------
    */

    public function test_every_role_updates_its_profile_and_photo(): void
    {
        Storage::fake('public');

        foreach ([User::factory()->create(), User::factory()->owner()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get(route('admin.profil.edit'))->assertOk()->assertSee('Mon profil');

            $this->actingAs($user)->put(route('admin.profil.update'), [
                'nom' => 'Nouveau Nom',
                'email' => $user->email,
                'telephone' => '+225 07 11 22 33 44',
                'ville' => 'Abidjan',
                'pays' => 'Côte d’Ivoire',
                'entreprise' => 'DS Résidences',
                'photo' => UploadedFile::fake()->image('moi.jpg', 300, 300),
            ])->assertRedirect()->assertSessionHas('success');

            $user->refresh();
            $this->assertSame('Nouveau Nom', $user->name);
            $this->assertSame('Abidjan', $user->city);
            Storage::disk('public')->assertExists($user->avatar_path);

            // Seul un propriétaire renseigne une entreprise
            $this->assertSame($user->isOwner() ? 'DS Résidences' : null, $user->company_name);
        }
    }

    public function test_changing_email_requires_a_new_confirmation(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('admin.profil.update'), [
            'nom' => $user->name,
            'email' => 'nouvelle@example.com',
        ])->assertRedirect(route('verification.notice'));

        $user->refresh();
        $this->assertSame('nouvelle@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_email_must_stay_unique(): void
    {
        User::factory()->create(['email' => 'prise@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('admin.profil.update'), ['nom' => $user->name, 'email' => 'prise@example.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('admin.profil.password'), [
            'mot_de_passe_actuel' => 'mauvais',
            'mot_de_passe' => 'Nouveau2026',
            'mot_de_passe_confirmation' => 'Nouveau2026',
        ])->assertSessionHasErrorsIn('password', 'mot_de_passe_actuel');

        $this->actingAs($user)->put(route('admin.profil.password'), [
            'mot_de_passe_actuel' => User::DEFAULT_PASSWORD,
            'mot_de_passe' => 'Nouveau2026',
            'mot_de_passe_confirmation' => 'Nouveau2026',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('Nouveau2026', $user->fresh()->password));
    }
}
