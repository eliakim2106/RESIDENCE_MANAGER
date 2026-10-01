<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Espace client (/mon-compte) : tableau de bord, réservations, annulation, avis, favoris, profil.
 */
class ClientSpaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_lands_on_its_space_after_login(): void
    {
        $client = User::factory()->create();

        $this->post(route('login.store'), ['email' => $client->email, 'password' => User::DEFAULT_PASSWORD])
            ->assertRedirect(route('client.dashboard'));

        $this->actingAs($client)->get(route('client.dashboard'))->assertOk()->assertSee('Mes réservations');
    }

    public function test_space_is_reserved_to_clients(): void
    {
        $this->get(route('client.dashboard'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->owner()->create())->get(route('client.dashboard'))->assertForbidden();
    }

    public function test_reservations_are_split_by_tab_and_private(): void
    {
        $client = User::factory()->create();
        $upcoming = Reservation::factory()->confirmed()->for($client)->create(['check_in' => now()->addDays(10), 'check_out' => now()->addDays(12)]);
        $cancelled = Reservation::factory()->cancelled()->for($client)->create();
        $someoneElse = Reservation::factory()->confirmed()->create();

        $this->actingAs($client)->get(route('client.reservations.index'))
            ->assertOk()->assertSee($upcoming->reference)->assertDontSee($cancelled->reference)->assertDontSee($someoneElse->reference);

        $this->actingAs($client)->get(route('client.reservations.index', ['onglet' => 'annulees']))
            ->assertOk()->assertSee($cancelled->reference);

        $this->actingAs($client)->get(route('client.reservations.show', $upcoming))->assertOk()->assertSee($upcoming->property->name);
        $this->actingAs($client)->get(route('client.reservations.show', $someoneElse))->assertForbidden();
    }

    public function test_client_cancels_a_pending_reservation(): void
    {
        $client = User::factory()->create();
        $reservation = Reservation::factory()->pending()->for($client)->create(['check_in' => now()->addDays(20), 'check_out' => now()->addDays(22)]);

        $this->actingAs($client)->patch(route('client.reservations.cancel', $reservation), ['motif' => 'Changement de programme'])
            ->assertRedirect(route('client.reservations.show', $reservation))
            ->assertSessionHas('success');

        $this->assertSame(ReservationStatus::Cancelled, $reservation->fresh()->statut);
    }

    public function test_client_reviews_a_past_stay_once(): void
    {
        $client = User::factory()->create();
        $reservation = Reservation::factory()->completed()->for($client)->create();

        $this->actingAs($client)->post(route('client.reservations.review', $reservation), [
            'note' => 9,
            'proprete' => 10,
            'commentaire' => 'Très bel appartement, accueil chaleureux.',
        ])->assertSessionHas('success');

        $property = $reservation->property->fresh();
        $this->assertSame(1, $property->reviews_count);
        $this->assertEquals(9, (float) $property->rating_average);

        $this->actingAs($client)->post(route('client.reservations.review', $reservation), ['note' => 2, 'commentaire' => 'Deuxième avis refusé.'])
            ->assertSessionHas('error');
        $this->assertSame(1, $property->reviews()->count());

        // Pas d'avis avant le séjour
        $future = Reservation::factory()->confirmed()->for($client)->create(['check_in' => now()->addDays(5), 'check_out' => now()->addDays(7)]);
        $this->actingAs($client)->post(route('client.reservations.review', $future), ['note' => 8, 'commentaire' => 'Avis trop tôt pour être publié.'])
            ->assertSessionHas('error');
    }

    public function test_client_toggles_a_favorite(): void
    {
        $client = User::factory()->create();
        $residence = Property::factory()->create(['name' => 'Villa Océane']);
        Unit::factory()->for($residence)->create();

        $this->actingAs($client)->post(route('client.favorites.toggle', $residence))->assertRedirect();
        $this->assertTrue($client->favorites()->whereKey($residence->id)->exists());
        $this->actingAs($client)->get(route('client.favorites.index'))->assertOk()->assertSee('Villa Océane');
        // Dans la liste des résidences, le cœur de la carte est plein et renvoie vers le même bouton
        $this->actingAs($client)->get(route('residences.index'))
            ->assertSee(route('client.favorites.toggle', $residence))
            ->assertSee('Retirer Villa Océane des favoris');

        $this->actingAs($client)->post(route('client.favorites.toggle', $residence));
        $this->assertFalse($client->favorites()->whereKey($residence->id)->exists());
    }

    public function test_client_updates_its_profile(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client)->put(route('client.profile.update'), [
            'nom' => 'Awa Koné',
            'email' => $client->email,
            'indicatif_telephone' => '+33',
            'telephone' => '6 12 34 56 78',
            'ville' => 'Paris',
            'pays' => 'France',
        ])->assertSessionHas('success');

        $client->refresh();
        $this->assertSame('Awa Koné', $client->name);
        $this->assertSame('+33', $client->indicatif_telephone);
        $this->assertSame('Paris', $client->city);
    }

    public function test_incomplete_profile_is_completed_first(): void
    {
        $client = User::factory()->create(['phone' => null, 'city' => null]);

        $this->actingAs($client)->get(route('client.dashboard'))->assertRedirect(route('client.profile.complete'));

        $this->actingAs($client)->put(route('client.profile.complete.store'), [
            'indicatif_telephone' => '+225',
            'telephone' => '0701020304',
            'ville' => 'Bouaké',
            'pays' => 'Côte d’Ivoire',
        ])->assertRedirect(route('client.dashboard'));

        $this->assertFalse($client->fresh()->needsProfileCompletion());
    }

    public function test_old_admin_links_lead_to_the_client_space(): void
    {
        $client = User::factory()->create();
        $reservation = Reservation::factory()->for($client)->create();

        $this->actingAs($client)->get(route('admin.reservations.show', $reservation))->assertRedirect(route('client.reservations.show', $reservation));
        $this->actingAs($client)->get(route('admin.profil.edit'))->assertRedirect(route('client.profile.edit'));
        // Le bon de réservation imprimable reste accessible
        $this->actingAs($client)->get(route('admin.reservations.voucher', $reservation))->assertOk();
    }
}
