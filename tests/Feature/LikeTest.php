<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use App\Services\PropertyLikes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * « J'aime » des établissements : visiteurs sans compte (un par navigateur), comptes connectés, lien avec les favoris.
 */
class LikeTest extends TestCase
{
    use RefreshDatabase;

    private Property $residence;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->residence = Property::factory()->create(['name' => 'Villa Océane']);
        Unit::factory()->for($this->residence)->create();
    }

    public function test_a_visitor_without_account_likes_once_per_browser(): void
    {
        $first = $this->postJson(route('residences.like', $this->residence))->assertOk()->assertJson(['liked' => true, 'count' => 1]);
        $visitor = $first->getCookie(PropertyLikes::COOKIE)->getValue();
        $this->assertTrue(Str::isUuid($visitor));

        // La fiche reconnaît ce navigateur : le cœur est plein
        $this->withCookie(PropertyLikes::COOKIE, $visitor)->get(route('residences.show', $this->residence))
            ->assertSee('aria-pressed="true"', false);

        // Un autre navigateur ajoute son j'aime
        $this->defaultCookies = [];
        $this->app['cookie']->flushQueuedCookies();
        $this->postJson(route('residences.like', $this->residence))->assertJson(['liked' => true, 'count' => 2]);

        // Second clic depuis le premier navigateur : le j'aime est retiré
        $this->withCredentials()->withCookie(PropertyLikes::COOKIE, $visitor)->postJson(route('residences.like', $this->residence))
            ->assertJson(['liked' => false, 'count' => 1]);
        $this->assertSame(1, $this->residence->fresh()->likes_count);
    }

    public function test_a_client_like_is_also_a_favorite(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client)->post(route('residences.like', $this->residence))
            ->assertRedirect()
            ->assertSessionHas('success', 'Vous aimez « Villa Océane » : retrouvez-le dans vos favoris.');
        $this->assertTrue($client->favorites()->whereKey($this->residence->id)->exists());
        $this->actingAs($client)->get(route('client.favorites.index'))->assertSee('Villa Océane');

        $this->actingAs($client)->postJson(route('residences.like', $this->residence))->assertJson(['liked' => false, 'count' => 0]);
        $this->assertFalse($client->favorites()->whereKey($this->residence->id)->exists());
    }

    public function test_other_accounts_like_without_favorites(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->postJson(route('residences.like', $this->residence))->assertJson(['liked' => true, 'count' => 1]);
        $this->assertSame(0, $owner->favorites()->count());
        $this->assertDatabaseHas('property_likes', ['property_id' => $this->residence->id, 'user_id' => $owner->id, 'visitor_id' => null]);
    }

    public function test_the_count_is_shown_on_cards_and_on_the_page(): void
    {
        $this->residence->likes()->createMany([['user_id' => User::factory()->create()->id], ['user_id' => User::factory()->create()->id]]);
        app(PropertyLikes::class)->refreshCount($this->residence);

        $this->get(route('residences.show', $this->residence))->assertSee('data-like-count', false)->assertSeeInOrder(['J’aime', '2']);
        $this->get(route('residences.index'))->assertSee(route('residences.like', $this->residence))->assertSee('aria-pressed="false"', false);
    }

    public function test_similar_residences_have_their_heart(): void
    {
        $client = User::factory()->create();
        $other = Property::factory()->create(['name' => 'Résidence Voisine', 'city_id' => $this->residence->city_id]);
        Unit::factory()->for($other)->create();

        $this->actingAs($client)->post(route('residences.like', $other));

        $this->actingAs($client)->get(route('residences.show', $this->residence))
            ->assertSee('Vous aimerez aussi')
            ->assertSee(route('residences.like', $other))
            ->assertSee('aria-label="J’aime Résidence Voisine" aria-pressed="true"', false);
    }

    public function test_an_offline_residence_cannot_be_liked(): void
    {
        $draft = Property::factory()->draft()->create();

        $this->postJson(route('residences.like', $draft))->assertNotFound();
        $this->assertSame(0, $draft->likes()->count());
    }
}
