<?php

namespace Tests\Feature\Admin;

use App\Enums\PropertyStatus;
use App\Models\Property;
use App\Models\User;
use App\Notifications\PropertyModerated;
use App\Notifications\PropertySubmitted;
use App\Services\PropertyModeration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Validation des établissements : soumission par le propriétaire, décisions de l'administrateur, notifications.
 */
class PropertyValidationTest extends TestCase
{
    use RefreshDatabase;

    private function pending(User $owner): Property
    {
        return Property::factory()->for($owner, 'owner')->create([
            'statut' => PropertyStatus::Pending,
            'submitted_at' => now()->subDay(),
            'published_at' => null,
        ]);
    }

    public function test_validation_queue_is_reserved_to_administrators(): void
    {
        $owner = User::factory()->owner()->create();
        $pending = $this->pending($owner);
        $published = Property::factory()->create(['statut' => PropertyStatus::Published]);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.validations.index'))
            ->assertOk()->assertSee($pending->name)->assertDontSee($published->name);

        $this->actingAs($owner)->get(route('admin.validations.index'))->assertForbidden();
        $this->actingAs($owner)->patch(route('admin.validations.approve', $pending))->assertForbidden();
        $this->assertSame(PropertyStatus::Pending, $pending->fresh()->statut);
    }

    public function test_admin_approves_a_pending_property_and_the_owner_is_notified(): void
    {
        Notification::fake();
        $owner = User::factory()->owner()->create();
        $admin = User::factory()->admin()->create();
        $property = $this->pending($owner);

        $this->actingAs($admin)->patch(route('admin.validations.approve', $property))->assertSessionHas('success');

        $property->refresh();
        $this->assertSame(PropertyStatus::Published, $property->statut);
        $this->assertNotNull($property->published_at);
        $this->assertTrue($property->moderator->is($admin));
        Notification::assertSentTo($owner, PropertyModerated::class, fn (PropertyModerated $notification) => $notification->decision === PropertyModerated::APPROVED);
    }

    public function test_rejection_requires_a_reason_sent_back_to_the_owner(): void
    {
        Notification::fake();
        $owner = User::factory()->owner()->create();
        $admin = User::factory()->admin()->create();
        $property = $this->pending($owner);

        $this->actingAs($admin)->patch(route('admin.validations.reject', $property), ['motif' => ''])->assertSessionHasErrors('motif');
        $this->assertSame(PropertyStatus::Pending, $property->fresh()->statut);

        $this->actingAs($admin)->patch(route('admin.validations.reject', $property), ['motif' => 'Les photos ne montrent pas l’établissement.'])
            ->assertSessionHas('success');

        $property->refresh();
        $this->assertSame(PropertyStatus::Draft, $property->statut);
        $this->assertTrue($property->wasRejected());
        Notification::assertSentTo($owner, PropertyModerated::class);

        // Le propriétaire voit le motif dans son formulaire
        $this->actingAs($owner)->get(route('admin.etablissements.edit', $property))
            ->assertOk()->assertSee('Demande de publication refusée')->assertSee('Les photos ne montrent pas l’établissement.');
    }

    public function test_admin_suspends_then_reinstates_a_published_property(): void
    {
        $admin = User::factory()->admin()->create();
        $property = Property::factory()->create(['statut' => PropertyStatus::Published, 'published_at' => now()->subMonth()]);

        $this->actingAs($admin)->patch(route('admin.validations.suspend', $property), ['motif' => 'Plaintes répétées de clients.'])
            ->assertSessionHas('success');
        $this->assertSame(PropertyStatus::Suspended, $property->fresh()->statut);

        // Une suspension n'est pas levée par le propriétaire lorsqu'il enregistre son formulaire
        $this->assertFalse($property->fresh()->isPublished());

        $this->actingAs($admin)->patch(route('admin.validations.reinstate', $property))->assertSessionHas('success');
        $property->refresh();
        $this->assertSame(PropertyStatus::Published, $property->statut);
        $this->assertNull($property->moderation_note);
    }

    public function test_an_action_that_does_not_match_the_status_is_refused(): void
    {
        $property = Property::factory()->create(['statut' => PropertyStatus::Published]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.validations.approve', $property))
            ->assertSessionHas('error');
    }

    public function test_submitting_a_property_notifies_administrators(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->for($owner, 'owner')->create(['statut' => PropertyStatus::Draft]);

        app(PropertyModeration::class)->applyVisibility($property, $owner, true);

        $this->assertSame(PropertyStatus::Pending, $property->fresh()->statut);
        Notification::assertSentTo($admin, PropertySubmitted::class);
        Notification::assertNotSentTo($owner, PropertySubmitted::class);
    }

    public function test_owner_cannot_lift_a_suspension_from_the_form(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->for($owner, 'owner')->create(['statut' => PropertyStatus::Suspended, 'moderation_note' => 'Contenu trompeur.']);

        app(PropertyModeration::class)->applyVisibility($property, $owner, true);

        $this->assertSame(PropertyStatus::Suspended, $property->fresh()->statut);
    }

    public function test_opening_a_notification_marks_it_as_read(): void
    {
        $owner = User::factory()->owner()->create();
        $property = Property::factory()->for($owner, 'owner')->create();
        $owner->notify(new PropertyModerated($property, PropertyModerated::APPROVED));
        $notification = $owner->unreadNotifications()->sole();

        $this->actingAs($owner)->get(route('admin.notifications.open', $notification->id))
            ->assertRedirect(route('admin.etablissements.edit', $property));

        $this->assertNotNull($notification->fresh()->read_at);

        // La notification d'un autre compte reste inaccessible
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.notifications.open', $notification->id))
            ->assertNotFound();
    }
}
