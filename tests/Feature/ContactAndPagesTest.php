<?php

namespace Tests\Feature;

use App\Enums\ContactMessageStatus;
use App\Enums\ReviewStatus;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\NewContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\ReadsExcelExports;
use Tests\TestCase;

/**
 * Accueil (données réelles), pages d'information, formulaire de contact, newsletter et pages d'erreur.
 */
class ContactAndPagesTest extends TestCase
{
    use ReadsExcelExports;
    use RefreshDatabase;

    public function test_home_shows_real_residences_and_reviews(): void
    {
        $residence = Property::factory()->create(['name' => 'Villa Océane', 'is_featured' => true]);
        Unit::factory()->for($residence)->create();
        $hidden = Property::factory()->draft()->create(['name' => 'Brouillon Caché']);
        // Publié mais sans unité : rien à réserver, donc absent de l'accueil
        Property::factory()->create(['name' => 'Résidence Sans Logement']);
        $client = User::factory()->create(['name' => 'Awa Koné']);
        Review::factory()->for($residence)->for($client)->for(Reservation::factory()->completed()->for($client)->for($residence))
            ->create(['rating' => 9, 'comment' => 'Un séjour parfait au bord de la mer.', 'statut' => ReviewStatus::Approved]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Villa Océane')
            ->assertSee(route('residences.show', $residence))
            ->assertDontSee('Brouillon Caché')
            ->assertDontSee('Résidence Sans Logement')
            ->assertSee('Un séjour parfait au bord de la mer.')
            ->assertSee('Awa K.')
            ->assertDontSee('5 000');
    }

    public function test_contact_message_is_stored_and_reported_to_admins(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $this->from(route('pages.contact'))->post(route('contact.store'), [
            'nom' => 'Yao Kouassi',
            'email' => 'Yao@Example.com',
            'indicatif_telephone' => '+225',
            'telephone' => '07 01 02 03 04',
            'sujet' => 'Séminaire',
            'message' => 'Bonjour, je cherche 10 chambres pour un séminaire.',
        ])->assertRedirect(route('pages.contact').'#contact')->assertSessionHas('contact_sent');

        $message = ContactMessage::sole();
        $this->assertSame('yao@example.com', $message->email);
        $this->assertSame('0701020304', $message->phone);
        $this->assertSame(ContactMessageStatus::New, $message->statut);
        Notification::assertSentTo($admin, NewContactMessage::class);
    }

    public function test_contact_form_validates_and_ignores_robots(): void
    {
        $this->post(route('contact.store'), ['nom' => '', 'email' => 'pas-un-email', 'sujet' => '', 'message' => 'Court'])
            ->assertSessionHasErrorsIn('contact', ['nom', 'email', 'sujet', 'message']);

        $this->post(route('contact.store'), ['nom' => 'Robot', 'email' => 'r@example.com', 'sujet' => 'Pub', 'message' => 'Achetez nos produits miracles', 'site_web' => 'http://spam.example'])
            ->assertSessionHas('contact_sent');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_admin_reads_answers_and_exports_messages(): void
    {
        $admin = User::factory()->admin()->create();
        $message = ContactMessage::factory()->create(['name' => 'Yao Kouassi', 'subject' => 'Séminaire', 'statut' => ContactMessageStatus::New]);

        $this->actingAs($admin)->get(route('admin.messages.index'))->assertOk()->assertSee('Yao Kouassi');
        $this->actingAs($admin)->get(route('admin.messages.show', $message))->assertOk()->assertSee('Séminaire');
        $this->assertSame(ContactMessageStatus::Read, $message->fresh()->statut);

        $this->actingAs($admin)->patch(route('admin.messages.update', $message), ['statut' => 'answered'])->assertSessionHas('success');
        $this->assertSame(ContactMessageStatus::Answered, $message->fresh()->statut);

        $this->assertStringContainsString('Yao Kouassi', $this->excelText($this->actingAs($admin)->get(route('admin.messages.export'))));

        $this->actingAs(User::factory()->owner()->create())->get(route('admin.messages.index'))->assertForbidden();
    }

    public function test_newsletter_subscription_and_unsubscription(): void
    {
        $this->post(route('newsletter.store'), ['email_newsletter' => 'Awa@Example.com'])->assertSessionHas('newsletter_sent');
        $this->post(route('newsletter.store'), ['email_newsletter' => 'awa@example.com'])->assertSessionHas('newsletter_sent');

        $subscriber = NewsletterSubscriber::sole();
        $this->assertSame('awa@example.com', $subscriber->email);

        $this->get(route('newsletter.unsubscribe', $subscriber->token))->assertOk()->assertSee('désinscrit');
        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);

        $this->post(route('newsletter.store'), ['email_newsletter' => 'invalide'])->assertSessionHasErrorsIn('newsletter', 'email_newsletter');
    }

    public function test_error_pages_are_branded(): void
    {
        $this->get('/page-qui-n-existe-pas')->assertNotFound()->assertSee('Page introuvable')->assertSee('Voir les résidences');

        Route::get('/_test/erreur-500', fn () => throw new \RuntimeException('Panne'));
        config(['app.debug' => false]);
        $this->get('/_test/erreur-500')->assertStatus(500)->assertSee('Une erreur est survenue')->assertDontSee('Panne');

        Route::get('/_test/refus', fn () => abort(403, 'Ce compte ne peut pas faire cette action.'));
        $this->get('/_test/refus')->assertForbidden()->assertSee('Ce compte ne peut pas faire cette action.');
    }
}
