<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Notifications : ouverture (quelle que soit l'adresse du site à l'envoi), flux du menu cloche, page et actions.
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->owner()->create();
    }

    private function notification(string $message, ?string $url = null, ?User $for = null, bool $read = false): DatabaseNotification
    {
        return ($for ?? $this->user)->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\Test',
            'data' => ['message' => $message, 'icon' => 'fa-wallet', 'tone' => 'good', 'url' => $url],
            'read_at' => $read ? now() : null,
        ]);
    }

    public function test_opening_follows_the_link_whatever_the_site_address_was(): void
    {
        $cases = [
            'http://127.0.0.1:8765/admin/mes-reversements' => '/admin/mes-reversements',
            'http://localhost/DS_HOLDING/RESIDENCE_MANAGER/public/admin/reservations/DS-ABC?onglet=1' => '/admin/reservations/DS-ABC?onglet=1',
            url('/admin/mon-abonnement') => '/admin/mon-abonnement',
        ];

        foreach ($cases as $stored => $expected) {
            $notification = $this->notification('Test', $stored);

            $this->actingAs($this->user)->get(route('admin.notifications.open', $notification->id))->assertRedirect(url($expected));
            $this->assertNotNull($notification->fresh()->read_at);
        }
    }

    public function test_links_outside_the_admin_area_are_never_followed(): void
    {
        foreach (['https://exemple-malveillant.com/vol', 'javascript:alert(1)', null] as $stored) {
            $notification = $this->notification('Test', $stored);

            $this->actingAs($this->user)->get(route('admin.notifications.open', $notification->id))
                ->assertRedirect(route('admin.notifications.index'));
        }

        // Même un autre domaine pointant vers /admin reste sur ce site
        $notification = $this->notification('Test', 'https://autre-site.com/admin/paiements');
        $this->actingAs($this->user)->get(route('admin.notifications.open', $notification->id))->assertRedirect(url('/admin/paiements'));
    }

    public function test_feed_returns_the_unread_count_and_the_menu(): void
    {
        $this->notification('Paiement reçu pour DS-1');
        $this->notification('Ancienne', read: true);

        $this->actingAs($this->user)->getJson(route('admin.notifications.feed'))
            ->assertOk()
            ->assertJson(['count' => 1])
            ->assertJsonPath('html', fn (string $html) => str_contains($html, 'Paiement reçu pour DS-1') && ! str_contains($html, 'Ancienne'));

        $this->actingAs($this->user)->postJson(route('admin.notifications.read-all'))->assertOk();
        $this->actingAs($this->user)->getJson(route('admin.notifications.feed'))
            ->assertJson(['count' => 0])
            ->assertJsonPath('html', fn (string $html) => str_contains($html, 'Aucune nouvelle notification'));
    }

    public function test_notifications_page_and_actions(): void
    {
        $unread = $this->notification('Nouvelle réservation');
        $read = $this->notification('Facture payée', read: true);

        $this->actingAs($this->user)->get(route('admin.notifications.index'))
            ->assertOk()->assertSee('Nouvelle réservation')->assertDontSee('Facture payée');
        $this->actingAs($this->user)->get(route('admin.notifications.index', ['statut' => 'toutes']))
            ->assertOk()->assertSee('Nouvelle réservation')->assertSee('Facture payée');

        $this->actingAs($this->user)->patch(route('admin.notifications.read', $unread->id))->assertRedirect();
        $this->assertNotNull($unread->fresh()->read_at);

        $this->actingAs($this->user)->delete(route('admin.notifications.destroy', $read->id))->assertSessionHas('success');
        $this->assertNull($read->fresh());

        $this->actingAs($this->user)->delete(route('admin.notifications.destroy-read'))->assertSessionHas('success');
        $this->assertSame(0, $this->user->notifications()->count());
    }

    public function test_menu_and_access_rules(): void
    {
        $this->notification('Votre essai gratuit a commencé.');

        $this->actingAs($this->user)->get(route('admin.notifications.index'))
            ->assertSee('Voir toutes les notifications')
            ->assertSee('data-feed-url', false);

        // La notification d'un autre compte reste inaccessible
        $other = $this->notification('Privé', for: User::factory()->create());
        $this->actingAs($this->user)->get(route('admin.notifications.open', $other->id))->assertNotFound();
        $this->actingAs($this->user)->patch(route('admin.notifications.read', $other->id))->assertNotFound();
        $this->actingAs($this->user)->delete(route('admin.notifications.destroy', $other->id))->assertNotFound();
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('admin.notifications.feed'))->assertRedirect(route('login'));
        $this->getJson(route('admin.notifications.feed'))->assertUnauthorized();
    }
}
