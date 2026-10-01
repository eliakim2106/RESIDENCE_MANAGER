<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Téléphones : indicatif séparé, saisie libre normalisée, longueur contrôlée selon le pays, affichage.
 */
class PhoneNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_are_normalized_whatever_the_input(): void
    {
        // Côte d'Ivoire : le 0 fait partie du numéro (10 chiffres)
        $this->assertSame('0701020304', PhoneNumber::normalize('+225', '07 01 02 03 04'));
        $this->assertSame('0701020304', PhoneNumber::normalize('+225', '+225 07 01 02 03 04'));
        $this->assertSame('0701020304', PhoneNumber::normalize('+225', '00225 0701020304'));

        // France : le 0 composé en local est retiré
        $this->assertSame('612345678', PhoneNumber::normalize('+33', '06 12 34 56 78'));
        $this->assertSame('612345678', PhoneNumber::normalize('+33', '+33 6 12 34 56 78'));

        // Sénégal, Bénin (10 chiffres depuis 2024)
        $this->assertSame('771234567', PhoneNumber::normalize('+221', '77 123 45 67'));
        $this->assertTrue(PhoneNumber::isValid('+229', PhoneNumber::normalize('+229', '01 97 12 34 56')));
    }

    public function test_length_depends_on_the_country(): void
    {
        $this->assertTrue(PhoneNumber::isValid('+225', '0701020304'));
        $this->assertFalse(PhoneNumber::isValid('+225', '07010203'));
        $this->assertTrue(PhoneNumber::isValid('+223', '65123456'));
        $this->assertFalse(PhoneNumber::isValid('+223', '6512345678'));
        $this->assertTrue(PhoneNumber::isValid('+32', '47012345'));
        $this->assertTrue(PhoneNumber::isValid('+32', '470123456'));
        $this->assertFalse(PhoneNumber::isValid('+999', '0701020304'));
    }

    public function test_display_and_international_format(): void
    {
        $this->assertSame('+225 07 01 02 03 04', PhoneNumber::format('+225', '0701020304'));
        $this->assertSame('+33 6 12 34 56 78', PhoneNumber::format('+33', '612345678'));
        $this->assertSame('+221 77 123 45 67', PhoneNumber::format('+221', '771234567'));
        $this->assertSame('+2250701020304', PhoneNumber::e164('+225', '0701020304'));

        $this->assertSame(['+225', '0701020304'], PhoneNumber::split('+225 07 01 02 03 04'));
        $this->assertSame(['+33', '612345678'], PhoneNumber::split('+33 6 12 34 56 78'));
        $this->assertSame(['+225', '0701020304'], PhoneNumber::split('0701020304'));
    }

    public function test_registration_checks_the_number_against_the_chosen_country(): void
    {
        $payload = fn (array $overrides) => [
            'nom' => 'Ndiaye',
            'prenoms' => 'Aminata',
            'email' => 'aminata@example.com',
            'pays' => 'Sénégal',
            'ville' => 'Dakar',
            'password' => 'Residence@2026',
            'confirm_password' => 'Residence@2026',
            'terms' => '1',
            ...$overrides,
        ];

        // 10 chiffres pour un numéro sénégalais : refusé avec un message clair
        $this->post(route('register.client.store'), $payload(['indicatif_telephone' => '+221', 'telephone' => '77 123 45 678']))
            ->assertSessionHasErrors(['telephone' => 'Un numéro Sénégal (+221) compte 9 chiffres, par exemple 77 123 45 67.']);

        // Indicatif inconnu refusé
        $this->post(route('register.client.store'), $payload(['indicatif_telephone' => '+999', 'telephone' => '0701020304']))
            ->assertSessionHasErrors('indicatif_telephone');

        // Numéro correct : inscription faite (l'utilisateur est alors connecté)
        $this->post(route('register.client.store'), $payload(['indicatif_telephone' => '+221', 'telephone' => '77 123 45 67']))
            ->assertSessionHasNoErrors();

        $user = User::firstWhere('email', 'aminata@example.com');
        $this->assertSame('+221', $user->indicatif_telephone);
        $this->assertSame('771234567', $user->phone);
        $this->assertSame('+221 77 123 45 67', $user->formattedPhone());

    }

    public function test_profile_accepts_a_foreign_number_and_an_empty_one(): void
    {
        $user = User::factory()->owner()->create();

        $this->actingAs($user)->put(route('admin.profil.update'), [
            'nom' => $user->name,
            'email' => $user->email,
            'indicatif_telephone' => '+33',
            'telephone' => '06 12 34 56 78',
        ])->assertSessionHasNoErrors();

        $this->assertSame('+33 6 12 34 56 78', $user->fresh()->formattedPhone());

        $this->actingAs($user)->put(route('admin.profil.update'), [
            'nom' => $user->name,
            'email' => $user->email,
            'indicatif_telephone' => '+33',
            'telephone' => '',
        ])->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->phone);

        $this->actingAs($user)->get(route('admin.profil.edit'))
            ->assertSee('data-phone-field', false)
            ->assertSee('name="indicatif_telephone"', false)
            ->assertSee('fi fi-fr', false);
    }
}
