<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Services\BookingEngine;
use App\Support\SiteSettings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Paramètres du site : réservés au super administrateur, appliqués au site public et à la réservation.
 */
class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'site_name' => 'Résidences Lagune',
            'site_description' => 'Appartements meublés à Abidjan, réservables en ligne.',
            'site_about' => 'Des appartements meublés au cœur d’Abidjan.',
            'contact_address' => 'Plateau, Abidjan',
            'contact_email' => 'bonjour@lagune.ci',
            'contact_phone_dial' => '+225',
            'contact_phone' => '07 08 09 10 11',
            'contact_whatsapp_dial' => '+225',
            'contact_whatsapp' => '',
            'contact_hours' => 'Du lundi au samedi, de 8 h à 19 h',
            'social_facebook' => 'https://www.facebook.com/lagune',
            'social_instagram' => '',
            'social_linkedin' => '',
            'social_tiktok' => '',
            'social_youtube' => '',
            'booking_request_ttl_hours' => 24,
            'booking_service_fee_rate' => 5,
            'booking_max_nights' => 30,
            'booking_max_days_ahead' => 180,
            ...$overrides,
        ];
    }

    public function test_page_is_reserved_to_the_super_administrator(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->get(route('admin.parametres.edit'))
            ->assertOk()
            ->assertSee('Paramètres du site')
            ->assertSee('contact@dsholding.ci');
        $this->actingAs($superAdmin)->get(route('dashboard'))->assertSee(route('admin.parametres.edit'));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.parametres.edit'))->assertForbidden();
        $this->actingAs($admin)->put(route('admin.parametres.update'), $this->payload())->assertForbidden();
        $this->actingAs($admin)->get(route('dashboard'))->assertDontSee(route('admin.parametres.edit'));

        $this->actingAs(User::factory()->owner()->create())->get(route('admin.parametres.edit'))->assertForbidden();
        $this->assertSame(0, Setting::count());
    }

    public function test_site_shows_the_default_values_until_changed(): void
    {
        $this->get(route('pages.contact'))
            ->assertOk()
            ->assertSee('contact@dsholding.ci')
            ->assertSee('+225 01 41 60 12 78')
            ->assertSee('https://wa.me/2250141601278');
    }

    public function test_saved_settings_are_used_by_the_site(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('admin.parametres.update'), $this->payload())
            ->assertRedirect(route('admin.parametres.edit'))
            ->assertSessionHas('success');

        $this->assertSame('0708091011', Setting::get('contact_phone'));

        $this->get(route('pages.contact'))
            ->assertOk()
            ->assertSee('Résidences Lagune • Tous droits réservés.')
            ->assertSee('bonjour@lagune.ci')
            ->assertSee('Plateau, Abidjan')
            ->assertSee('+225 07 08 09 10 11')
            ->assertSee('Du lundi au samedi, de 8 h à 19 h')
            ->assertSee('https://www.facebook.com/lagune')
            ->assertDontSee('wa.me')                    // WhatsApp effacé : plus affiché
            ->assertDontSee('contact@dsholding.ci');

        // Règles de réservation
        $site = app(SiteSettings::class);
        $this->assertSame(24, $site->booking('request_ttl_hours'));
        $this->assertSame(5.0, BookingEngine::serviceFeeRate());

        $this->expectExceptionMessage('Séjour de 30 nuits maximum en ligne');
        app(BookingEngine::class)->assertDates(CarbonImmutable::today()->addDay(), CarbonImmutable::today()->addDays(40));
    }

    public function test_changes_are_traced_with_their_author(): void
    {
        $awa = User::factory()->superAdmin()->create(['name' => 'Awa Koné']);
        $yao = User::factory()->superAdmin()->create(['name' => 'Yao Kouassi']);

        $this->actingAs($awa)->get(route('admin.parametres.edit'))->assertSee('Valeurs d’origine');

        $this->actingAs($awa)->put(route('admin.parametres.update'), $this->payload());
        $this->assertSame($awa->id, Setting::query()->where('key', 'site_name')->value('updated_by'));

        // Même valeur renvoyée par un autre compte : l'auteur de la modification ne change pas
        $this->travel(5)->minutes();
        $this->actingAs($yao)->put(route('admin.parametres.update'), $this->payload(['site_about' => 'Nouvelle présentation du site.']));
        $this->assertSame($awa->id, Setting::query()->where('key', 'site_name')->value('updated_by'));
        $this->assertSame($yao->id, Setting::query()->where('key', 'site_about')->value('updated_by'));

        $this->actingAs($awa)->get(route('admin.parametres.edit'))->assertSee('par <strong>Yao Kouassi</strong>', false);
    }

    public function test_invalid_values_are_rejected_and_nothing_is_saved(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('admin.parametres.update'), $this->payload([
                'site_name' => '',
                'contact_phone' => '12',
                'social_facebook' => 'facebook.com/lagune',
                'booking_request_ttl_hours' => 0,
                'booking_service_fee_rate' => 50,
            ]))
            ->assertSessionHasErrors(['site_name', 'contact_phone', 'social_facebook', 'booking_request_ttl_hours', 'booking_service_fee_rate']);

        $this->assertSame(0, Setting::count());
    }

    public function test_booking_rules_fall_back_on_the_configuration(): void
    {
        config(['booking.max_nights' => 45]);

        $this->assertSame(45, app(SiteSettings::class)->booking('max_nights'));
    }
}
