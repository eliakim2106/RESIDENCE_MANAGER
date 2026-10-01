<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Enums\CancellationPolicy;
use App\Enums\PropertyStatus;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PropertyRequest;
use App\Models\City;
use App\Models\Property;
use App\Models\PropertyType;
use App\Services\GalleryManager;
use App\Services\PropertyInsights;
use App\Services\PropertyListing;
use App\Services\PropertyModeration;
use App\Services\SubscriptionManager;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Établissements : liste, fiche, formulaire en 6 étapes, publication et suppression.
 * Les administrateurs voient tout, un propriétaire uniquement les siens (PropertyPolicy).
 */
class PropertyController extends Controller
{
    /**
     * Étapes du formulaire, dans l'ordre (?etape=medias ouvre directement les médias).
     */
    public const STEPS = ['informations', 'localisation', 'accueil', 'medias', 'publication', 'seo'];

    public function __construct(
        private GalleryManager $gallery,
        private PropertyModeration $moderation,
        private SubscriptionManager $subscriptions,
    ) {}

    public function index(Request $request): View
    {
        $listing = new PropertyListing($request->user(), $request);

        return view('admin.etablissements.index', [
            'listing' => $listing,
            'etablissements' => $listing->paginate(),
            'counts' => $listing->counts(),
            'summary' => $listing->summary(),
        ]);
    }

    /**
     * Fiche de l'établissement : indicateurs, unités, prochaines arrivées, avis et actions.
     */
    public function show(Property $etablissement): View
    {
        Gate::authorize('view', $etablissement);

        $etablissement->load(['propertyType', 'city', 'owner.currentSubscription.plan', 'moderator', 'images' => fn ($query) => $query->orderByDesc('is_cover')->orderBy('position')]);
        $insights = new PropertyInsights($etablissement);

        return view('admin.etablissements.show', [
            'etablissement' => $etablissement,
            'kpis' => $insights->kpis(),
            'arrivals' => $insights->upcomingArrivals(),
            'reviews' => $insights->latestReviews(),
            'units' => $etablissement->units()->with(['unitType', 'images'])->orderBy('name')->get(),
            'activeReservations' => $insights->activeReservations(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        Gate::authorize('create', Property::class);

        if ($blocker = $this->subscriptions->propertyBlocker($request->user())) {
            return redirect()->route('admin.abonnement.show')->with('error', $blocker);
        }

        return view('admin.etablissements.create', [
            'etablissement' => new Property,
            ...$this->formOptions(),
        ]);
    }

    public function store(PropertyRequest $request): RedirectResponse
    {
        Gate::authorize('create', Property::class);

        if ($blocker = $this->subscriptions->propertyBlocker($request->user())) {
            return redirect()->route('admin.abonnement.show')->with('error', $blocker);
        }

        $property = DB::transaction(function () use ($request): Property {
            $property = Property::create([
                ...$request->propertyAttributes(),
                'owner_id' => $request->user()->id,
                // Toujours créé en brouillon : la publication ou la soumission se fait ensuite (PropertyModeration)
                'statut' => PropertyStatus::Draft,
            ]);

            $this->syncMedia($property, $request);
            $this->moderation->applyVisibility($property, $request->user(), $request->wantsOnline());

            return $property;
        });

        return redirect()->route('admin.etablissements.show', $property)
            ->with('success', $property->isPending()
                ? 'Établissement créé et envoyé pour validation. Il sera publié dès qu’un administrateur l’aura approuvé.'
                : 'Établissement créé avec succès.');
    }

    public function edit(Request $request, Property $etablissement): View
    {
        Gate::authorize('update', $etablissement);

        $etablissement->load(['images' => fn ($query) => $query->orderByDesc('is_cover')->orderBy('position')]);
        $step = array_search((string) $request->query('etape'), self::STEPS, true);

        return view('admin.etablissements.edit', [
            'etablissement' => $etablissement,
            'startStep' => $step === false ? 1 : $step + 1,
            ...$this->formOptions(),
        ]);
    }

    public function update(PropertyRequest $request, Property $etablissement): RedirectResponse
    {
        Gate::authorize('update', $etablissement);

        $wasPending = $etablissement->isPending();

        DB::transaction(function () use ($request, $etablissement): void {
            $etablissement->update($request->propertyAttributes());

            $this->syncMedia($etablissement, $request);
            $this->moderation->applyVisibility($etablissement, $request->user(), $request->wantsOnline());
        });

        return redirect()->route('admin.etablissements.show', $etablissement)
            ->with('success', $etablissement->isPending() && ! $wasPending
                ? 'Établissement envoyé pour validation. Il sera publié dès qu’un administrateur l’aura approuvé.'
                : 'Établissement modifié avec succès.');
    }

    public function destroy(Property $etablissement): RedirectResponse
    {
        Gate::authorize('delete', $etablissement);

        // Des clients ont encore un séjour à venir ou en cours : on ne retire pas l'établissement sous leurs pieds
        $active = (new PropertyInsights($etablissement))->activeReservations();

        if ($active > 0) {
            return back()->with('error', "« {$etablissement->name} » a encore {$active} réservation".($active > 1 ? 's' : '').' en attente ou à venir. '
                .'Annulez-les ou attendez la fin des séjours, puis mettez l’établissement hors ligne en attendant.');
        }

        $etablissement->delete();

        return redirect()->route('admin.etablissements.index')
            ->with('success', "« {$etablissement->name} » a été supprimé.");
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLICATION DEPUIS LA FICHE
    |--------------------------------------------------------------------------
    */

    /**
     * Propriétaire : envoie un brouillon (ou un établissement refusé corrigé) à la validation.
     */
    public function submit(Request $request, Property $etablissement): RedirectResponse
    {
        Gate::authorize('update', $etablissement);

        if ($blocker = $this->publicationBlocker($etablissement)) {
            return back()->with('error', $blocker);
        }

        return $this->moderate(fn () => $this->moderation->submit($etablissement), 'Établissement envoyé pour validation. Il sera publié dès qu’un administrateur l’aura approuvé.');
    }

    /**
     * Administrateur : publie directement (brouillon ou demande en attente).
     */
    public function publish(Request $request, Property $etablissement): RedirectResponse
    {
        Gate::authorize('moderate', $etablissement);

        if ($blocker = $this->publicationBlocker($etablissement)) {
            return back()->with('error', $blocker);
        }

        return $this->moderate(fn () => $this->moderation->applyVisibility($etablissement, $request->user(), true), "« {$etablissement->name} » est publié.");
    }

    /**
     * Retire l'établissement du site (retour en brouillon) : les réservations existantes sont conservées.
     */
    public function unpublish(Request $request, Property $etablissement): RedirectResponse
    {
        Gate::authorize('update', $etablissement);

        if ($etablissement->isSuspended() && ! $request->user()->isAdmin()) {
            return back()->with('error', 'Seul un administrateur peut lever une suspension.');
        }

        return $this->moderate(fn () => $this->moderation->applyVisibility($etablissement, $request->user(), false), "« {$etablissement->name} » est hors ligne : il n’apparaît plus sur le site.");
    }

    /**
     * Ce qui empêche la mise en ligne : aucune unité active ou aucune photo.
     */
    private function publicationBlocker(Property $property): ?string
    {
        return match (true) {
            ! $property->units()->where('statut', ActiveStatus::Active)->exists() => 'Ajoutez au moins une unité active (chambre, appartement…) avant de publier l’établissement.',
            ! $property->images()->exists() => 'Ajoutez au moins une photo avant de publier l’établissement.',
            default => null,
        };
    }

    private function moderate(Closure $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (WorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $success);
    }

    private function syncMedia(Property $property, PropertyRequest $request): void
    {
        $directory = "properties/{$property->id}";

        $property->update([
            'logo_path' => $this->gallery->replace(
                $property->logo_path,
                $request->file('logo'),
                $request->boolean('deleted_logo'),
                "{$directory}/logo",
            ),
        ]);

        $this->gallery->sync(
            fn () => $property->images(),
            "{$directory}/gallery",
            $request->deletedGalleryIds(),
            $request->file('gallery', []),
            $request->input('gallery_cover'),
            hasCoverColumn: true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'typesEtablissement' => PropertyType::active()->orderBy('name')->get(),
            'villes' => City::active()->orderBy('name')->get(),
            'cancellationPolicies' => CancellationPolicy::cases(),
        ];
    }
}
