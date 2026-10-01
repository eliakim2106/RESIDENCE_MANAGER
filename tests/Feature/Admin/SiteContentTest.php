<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Contenu des pages (Paramètres du site) : diaporama, sections de l'accueil, en-têtes, questions fréquentes.
 */
class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    /**
     * Formulaire d'une page rempli avec le contenu d'origine (blocs affichés).
     *
     * @return array<string, mixed>
     */
    private function form(string $page): array
    {
        $blocks = [];

        foreach (config("site-content.pages.{$page}.blocks") as $block) {
            $definition = config("site-content.blocks.{$block}");
            $blocks[$block] = $definition['defaults'];

            if ($definition['visible'] ?? false) {
                $blocks[$block]['visible'] = '1';
            }
        }

        return ['blocks' => $blocks];
    }

    public function test_pages_are_reserved_to_the_super_administrator(): void
    {
        foreach (array_keys(config('site-content.pages')) as $page) {
            $this->actingAs($this->superAdmin)->get(route('admin.parametres.contenu', $page))->assertOk()->assertSee('Contenu des pages');
        }

        $this->actingAs($this->superAdmin)->get(route('admin.parametres.contenu', 'accueil'))
            ->assertSee('Diaporama d’accueil')
            ->assertSee('Trouvez votre');

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.parametres.contenu', 'accueil'))->assertForbidden();
        $this->actingAs($admin)->put(route('admin.parametres.contenu.update', 'accueil'), $this->form('accueil'))->assertForbidden();
        $this->actingAs($this->superAdmin)->get('/admin/parametres/contenu/inconnue')->assertNotFound();
    }

    public function test_home_page_content_is_edited_reordered_and_hidden(): void
    {
        $form = $this->form('accueil');
        [$first, $second, $third] = $form['blocks']['home_slides']['items'];

        // Nouvel ordre, une diapositive retirée, une nouvelle image ; la section « étapes » masquée
        $form['blocks']['home_slides']['items'] = [
            7 => [...$third, 'title' => 'Escapade à Assinie', 'image_file' => UploadedFile::fake()->image('plage.jpg', 1600, 900)],
            2 => [...$first, 'title' => 'Bienvenue chez nous'],
        ];
        $form['blocks']['home_steps']['visible'] = '0';
        $form['blocks']['home_cta']['title'] = 'Votre prochain séjour commence ici';

        $this->actingAs($this->superAdmin)->put(route('admin.parametres.contenu.update', 'accueil'), $form)
            ->assertRedirect(route('admin.parametres.contenu', 'accueil'))
            ->assertSessionHas('success');

        $slides = json_decode(Setting::get('content.home_slides'), true)['items'];
        $this->assertSame(['Escapade à Assinie', 'Bienvenue chez nous'], array_column($slides, 'title'));
        $this->assertStringStartsWith('site/', $slides[0]['image']);
        Storage::disk('public')->assertExists($slides[0]['image']);

        $home = $this->get(route('home'))->assertOk();
        $home->assertSeeInOrder(['Escapade à Assinie', 'Bienvenue chez nous'])
            ->assertDontSee($second['title'])
            ->assertSee(Storage::disk('public')->url($slides[0]['image']))
            ->assertSee('Votre prochain séjour commence ici')
            ->assertDontSee('home-steps-list', false);
    }

    public function test_invalid_content_is_rejected(): void
    {
        $form = $this->form('accueil');
        $form['blocks']['home_slides']['items'] = [];
        $form['blocks']['home_cta']['primary_link'] = 'javascript:alert(1)';
        $form['blocks']['home_why']['items'][0]['icon'] = 'fa-skull';
        $form['blocks']['home_why']['image'] = '../../.env';

        $this->actingAs($this->superAdmin)->put(route('admin.parametres.contenu.update', 'accueil'), $form)
            ->assertSessionHasErrors([
                'blocks.home_slides.items',
                'blocks.home_cta.primary_link',
                'blocks.home_why.items.0.icon',
                'blocks.home_why.image',
            ]);

        $this->assertSame(0, Setting::count());
    }

    public function test_a_block_returns_to_its_original_content(): void
    {
        $form = $this->form('residences');
        $form['blocks']['listing_hero']['title'] = 'Nos adresses';
        $form['blocks']['listing_hero']['image_file'] = UploadedFile::fake()->image('hero.jpg');

        $this->actingAs($this->superAdmin)->put(route('admin.parametres.contenu.update', 'residences'), $form);
        $image = json_decode(Setting::get('content.listing_hero'), true)['image'];
        $this->get(route('residences.index'))->assertSee('Nos adresses');

        // Une nouvelle image remplace l'ancienne : l'ancien fichier est supprimé
        $form['blocks']['listing_hero']['image'] = $image;
        $form['blocks']['listing_hero']['image_file'] = UploadedFile::fake()->image('hero-2.jpg');
        $this->actingAs($this->superAdmin)->put(route('admin.parametres.contenu.update', 'residences'), $form);
        $newImage = json_decode(Setting::get('content.listing_hero'), true)['image'];
        Storage::disk('public')->assertMissing($image);
        Storage::disk('public')->assertExists($newImage);

        $this->actingAs($this->superAdmin)->delete(route('admin.parametres.contenu.reset', ['residences', 'listing_hero']))
            ->assertSessionHas('success');

        Storage::disk('public')->assertMissing($newImage);
        $this->assertNull(Setting::get('content.listing_hero'));
        $this->get(route('residences.index'))->assertSee('Trouvez la résidence')->assertDontSee('Nos adresses');
    }

    public function test_faq_questions_are_grouped_by_theme(): void
    {
        $form = $this->form('informations');
        $form['blocks']['faq_hero']['title'] = 'Vos questions';
        $form['blocks']['faq_questions']['items'] = [
            ['theme' => 'Séjour', 'question' => 'Le ménage est-il inclus ?', 'answer' => 'Oui, selon l’établissement.'],
            ['theme' => 'Paiement', 'question' => 'Puis-je payer par Wave ?', 'answer' => 'Oui, selon disponibilité.'],
            ['theme' => 'Séjour', 'question' => 'Puis-je arriver tard ?', 'answer' => 'Indiquez votre heure d’arrivée.'],
        ];

        $this->actingAs($this->superAdmin)->put(route('admin.parametres.contenu.update', 'informations'), $form)->assertSessionHas('success');

        $this->get(route('pages.faq'))
            ->assertOk()
            ->assertSee('Vos questions')
            ->assertSeeInOrder(['Séjour', 'Le ménage est-il inclus ?', 'Puis-je arriver tard ?', 'Paiement', 'Puis-je payer par Wave ?'])
            ->assertDontSee('Comment réserver une résidence ?');
    }
}
