<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render(): void
    {
        foreach (['home', 'residences.index', 'login', 'register', 'register.client', 'register.owner', 'password.request', 'pages.faq', 'pages.contact', 'pages.owners', 'pages.conditions', 'pages.privacy'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_guest_is_redirected_from_the_back_office_to_the_login_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_client_cannot_manage_accommodations(): void
    {
        $client = User::factory()->create();

        // Le client est renvoyé vers son espace ; les actions d'écriture restent refusées
        $this->actingAs($client)->get(route('dashboard'))->assertRedirect(route('client.dashboard'));
        $this->actingAs($client)->get(route('admin.etablissements.index'))->assertRedirect(route('client.dashboard'));
        $this->actingAs($client)->post(route('admin.types-etablissement.store'), ['nom' => 'Test'])->assertForbidden();
    }

    public function test_owner_cannot_manage_the_platform_catalogs(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get(route('admin.etablissements.index'))->assertOk();
        $this->actingAs($owner)->get(route('admin.types-etablissement.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('admin.equipements.index'))->assertForbidden();
    }
}
