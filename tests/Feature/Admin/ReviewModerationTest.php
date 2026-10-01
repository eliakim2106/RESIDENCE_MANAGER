<?php

namespace Tests\Feature\Admin;

use App\Enums\ReviewStatus;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\ReviewActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\ReadsExcelExports;
use Tests\TestCase;

/**
 * Avis : notification du propriétaire, réponse publique, signalement, masquage et remise en ligne.
 */
class ReviewModerationTest extends TestCase
{
    use ReadsExcelExports;
    use RefreshDatabase;

    private User $owner;

    private User $guest;

    private Property $residence;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->owner()->create(['name' => 'Yao Propriétaire']);
        $this->guest = User::factory()->create(['name' => 'Awa Koné']);
        $this->residence = Property::factory()->for($this->owner, 'owner')->create(['name' => 'Villa Océane']);
        Unit::factory()->for($this->residence)->create();
    }

    private function review(array $attributes = []): Review
    {
        $review = Review::factory()
            ->for($this->residence)
            ->for($this->guest)
            ->for(Reservation::factory()->completed()->for($this->guest)->for($this->residence))
            ->create(['rating' => 4, 'comment' => 'Chambre sale et accueil désagréable.', 'statut' => ReviewStatus::Approved, ...$attributes]);
        $this->residence->refreshRating();

        return $review;
    }

    public function test_owner_is_notified_of_a_new_review(): void
    {
        Notification::fake();
        $reservation = Reservation::factory()->completed()->for($this->guest)->for($this->residence)->create();

        $this->actingAs($this->guest)->post(route('client.reservations.review', $reservation), ['note' => 9, 'commentaire' => 'Séjour parfait, je recommande.']);

        Notification::assertSentTo($this->owner, ReviewActivity::class, fn (ReviewActivity $n) => $n->event === ReviewActivity::NEW_FOR_OWNER);
    }

    public function test_each_account_sees_its_reviews(): void
    {
        $mine = $this->review(['title' => 'Avis sur ma villa']);
        $other = Review::factory()->create(['title' => 'Avis ailleurs', 'statut' => ReviewStatus::Approved]);

        $this->actingAs($this->owner)->get(route('admin.avis.index'))->assertOk()->assertSee('Avis sur ma villa')->assertDontSee('Avis ailleurs');
        $this->actingAs($this->owner)->get(route('admin.avis.show', $other))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.avis.index'))->assertSee('Avis sur ma villa')->assertSee('Avis ailleurs');
        $this->actingAs($this->guest)->get(route('admin.avis.show', $mine))->assertRedirect(route('client.dashboard'));
    }

    public function test_owner_replies_publicly_and_the_guest_is_told_once(): void
    {
        Notification::fake();
        $review = $this->review();

        $this->actingAs($this->owner)->put(route('admin.avis.reply', $review), ['reponse' => 'Merci pour votre retour, nous avons changé de prestataire de ménage.'])
            ->assertSessionHas('success');
        $this->actingAs($this->owner)->put(route('admin.avis.reply', $review), ['reponse' => 'Merci pour votre retour : le ménage est désormais contrôlé chaque jour.']);

        Notification::assertSentToTimes($this->guest, ReviewActivity::class, 1);
        $this->get(route('residences.show', $this->residence))->assertSee('Réponse de l’établissement')->assertSee('le ménage est désormais contrôlé chaque jour.');

        // Un administrateur ne répond pas à la place de l'établissement
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.avis.reply', $review), ['reponse' => 'Réponse de l’administrateur'])->assertForbidden();

        // Réponse vide : retirée
        $this->actingAs($this->owner)->put(route('admin.avis.reply', $review), ['reponse' => '']);
        $this->assertNull($review->fresh()->owner_reply);
    }

    public function test_report_then_hide_then_publish_again(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $review = $this->review();
        $this->assertSame(1, $this->residence->fresh()->reviews_count);

        // Signalement par le propriétaire : motif obligatoire, une seule fois
        $this->actingAs($this->owner)->post(route('admin.avis.report', $review), ['motif' => ''])->assertSessionHasErrors('motif');
        $this->actingAs($this->owner)->post(route('admin.avis.report', $review), ['motif' => 'Ce voyageur n’a jamais séjourné dans cette chambre.'])->assertSessionHas('success');
        $this->actingAs($this->owner)->post(route('admin.avis.report', $review), ['motif' => 'Second signalement du même avis.'])->assertForbidden();
        Notification::assertSentTo($admin, ReviewActivity::class, fn (ReviewActivity $n) => $n->event === ReviewActivity::REPORTED_FOR_ADMIN);
        $this->actingAs($admin)->get(route('admin.avis.index', ['statut' => 'signales']))->assertSee('jamais séjourné');

        // Le propriétaire ne modère pas lui-même
        $this->actingAs($this->owner)->patch(route('admin.avis.hide', $review), ['motif' => 'Je préfère le masquer'])->assertForbidden();

        // Masquage : hors du site et de la note
        $this->actingAs($admin)->patch(route('admin.avis.hide', $review), ['motif' => 'Propos sans rapport avec le séjour.'])->assertSessionHas('success');
        $review->refresh();
        $this->assertSame(ReviewStatus::Rejected, $review->statut);
        $this->assertSame(0, $this->residence->fresh()->reviews_count);
        $this->get(route('residences.show', $this->residence))->assertDontSee('Chambre sale et accueil désagréable.');
        Notification::assertSentTo($this->owner, ReviewActivity::class, fn (ReviewActivity $n) => $n->event === ReviewActivity::HIDDEN_FOR_OWNER);
        Notification::assertSentTo($this->guest, ReviewActivity::class, fn (ReviewActivity $n) => $n->event === ReviewActivity::HIDDEN_FOR_GUEST);
        $this->actingAs($this->owner)->put(route('admin.avis.reply', $review), ['reponse' => 'Réponse à un avis masqué.'])->assertForbidden();

        // Remise en ligne : signalement clos, note recalculée
        $this->actingAs($admin)->patch(route('admin.avis.publish', $review))->assertSessionHas('success');
        $review->refresh();
        $this->assertTrue($review->isPublished());
        $this->assertFalse($review->isReported());
        $this->assertSame(1, $this->residence->fresh()->reviews_count);
    }

    public function test_reviews_are_exported(): void
    {
        $this->review(['title' => 'Séjour décevant']);

        $text = $this->excelText($this->actingAs($this->owner)->get(route('admin.avis.export')));

        $this->assertStringContainsString('Séjour décevant', $text);
        $this->assertStringContainsString('Villa Océane', $text);
    }
}
