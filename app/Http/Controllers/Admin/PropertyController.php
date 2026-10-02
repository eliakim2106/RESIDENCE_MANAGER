<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Enums\CancellationPolicy;
use App\Enums\PropertyStatus;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PropertyRequest;
use App\Models\City;
use App\Models\Equipment;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\UnitType;
use App\Services\GalleryManager;
use App\Services\PropertyInsights;
use App\Services\PropertyListing;
use App\Services\PropertyModeration;
use App\Services\SubscriptionManager;
use App\Services\WholeUnit;
use App\Support\ExcelExport;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        private WholeUnit $wholeUnit,
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
     * Export Excel des établissements affichés (mêmes filtres que la liste).
     */
    public function export(Request $request): BinaryFileResponse
    {
        $listing = new PropertyListing($request->user(), $request);
        $isAdmin = $request->user()->isAdmin();

        $columns = [
            ['label' => 'Établissement', 'width' => 30],
            ['label' => 'Type', 'width' => 18],
            ['label' => 'Ville', 'width' => 16],
            ['label' => 'Commune', 'width' => 16],
            ['label' => 'Adresse', 'width' => 30],
            ['label' => 'Téléphone', 'width' => 20],
            ['label' => 'Email', 'width' => 26],
            ['label' => 'Étoiles', 'type' => 'number', 'width' => 8],
            ['label' => 'Unités', 'type' => 'number', 'width' => 8],
            ['label' => 'Réservations à venir', 'type' => 'number', 'width' => 14],
            ['label' => 'Encaissé ce mois', 'type' => 'money', 'width' => 18],
            ['label' => 'Note', 'type' => 'decimal', 'width' => 8],
            ['label' => 'Avis', 'type' => 'number', 'width' => 8],
            ['label' => 'Annulation', 'width' => 12],
            ['label' => 'Statut', 'width' => 14],
            ['label' => 'Publié le', 'type' => 'date'],
        ];

        if ($isAdmin) {
            array_splice($columns, 1, 0, [['label' => 'Propriétaire', 'width' => 24]]);
        }

        return ExcelExport::download('etablissements', 'Établissements', $columns, $listing->export()->map(function (Property $property) use ($isAdmin): array {
            $row = [
                $property->name,
                $property->propertyType?->name,
                $property->city?->name,
                $property->district,
                $property->address,
                $property->phone ? $property->formattedPhone() : '',
                $property->email,
                $property->star_rating,
                (int) $property->units_count,
                (int) $property->upcoming_count,
                (int) $property->month_revenue,
                $property->reviews_count > 0 ? (float) $property->rating_average : null,
                (int) $property->reviews_count,
                $property->cancellation_policy ? Str::before($property->cancellation_policy->label(), ' (') : '',
                $property->wasRejected() ? 'Refusé' : $property->statut->label(),
                $property->published_at,
            ];

            if ($isAdmin) {
                array_splice($row, 1, 0, [$property->owner?->name]);
            }

            return $row;
        }));
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
            'logement' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(PropertyRequest $request): RedirectResponse
    {
        Gate::authorize('create', Property::class);

        if ($blocker = $this->subscriptions->propertyBlocker($request->user())) {
            return redirect()->route('admin.abonnement.show')->with('error', $blocker);
        }

        // Un logement entier crée aussi son unité : la formule doit le permettre
        if ($request->isWholeHome() && ($blocker = $this->wholeUnitBlocker($request))) {
            return back()->withInput()->with('error', $blocker);
        }

        [$property, $blocked] = DB::transaction(function () use ($request): array {
            $property = Property::create([
                ...$request->propertyAttributes(),
                'owner_id' => $request->user()->id,
                // Toujours créé en brouillon : la publication ou la soumission se fait ensuite (PropertyModeration)
                'statut' => PropertyStatus::Draft,
            ]);

            $this->syncMedia($property, $request);
            $this->syncWholeUnit($property, $request);

            return [$property, $this->applyVisibility($property, $request)];
        });

        return redirect()->route('admin.etablissements.show', $property)
            ->with(...match (true) {
                $blocked !== null => ['error', "Établissement enregistré en brouillon. {$blocked}"],
                $property->isPending() => ['success', 'Établissement créé et envoyé pour validation. Il sera publié dès qu’un administrateur l’aura approuvé.'],
                default => ['success', 'Établissement créé avec succès.'],
            });
    }

    public function edit(Request $request, Property $etablissement): View
    {
        Gate::authorize('update', $etablissement);

        $etablissement->load(['images' => fn ($query) => $query->orderByDesc('is_cover')->orderBy('position')]);
        $step = array_search((string) $request->query('etape'), self::STEPS, true);

        return view('admin.etablissements.edit', [
            'etablissement' => $etablissement,
            'logement' => $this->wholeUnit->of($etablissement),
            'startStep' => $step === false ? 1 : $step + 1,
            ...$this->formOptions(),
        ]);
    }

    public function update(PropertyRequest $request, Property $etablissement): RedirectResponse
    {
        Gate::authorize('update', $etablissement);

        if ($request->isWholeHome() && ! $etablissement->units()->exists() && ($blocker = $this->wholeUnitBlocker($request))) {
            return back()->withInput()->with('error', $blocker);
        }

        $wasPending = $etablissement->isPending();

        $blocked = DB::transaction(function () use ($request, $etablissement): ?string {
            $etablissement->update($request->propertyAttributes());

            $this->syncMedia($etablissement, $request);
            $this->syncWholeUnit($etablissement, $request);

            return $this->applyVisibility($etablissement, $request);
        });

        return redirect()->route('admin.etablissements.show', $etablissement)
            ->with(...match (true) {
                $blocked !== null => ['error', ($etablissement->statut === PropertyStatus::Draft ? 'Établissement enregistré en brouillon. ' : 'Établissement enregistré, mais il n’apparaît pas sur le site. ').$blocked],
                $etablissement->isPending() && ! $wasPending => ['success', 'Établissement envoyé pour validation. Il sera publié dès qu’un administrateur l’aura approuvé.'],
                default => ['success', 'Établissement modifié avec succès.'],
            });
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

    /**
     * Applique le choix « en ligne / brouillon » du formulaire. Un établissement qui ne peut pas être réservé
     * (aucune unité active, aucune photo) n'est ni soumis ni publié : renvoie la raison, ou null.
     */
    private function applyVisibility(Property $property, PropertyRequest $request): ?string
    {
        $blocker = $request->wantsOnline() ? $this->publicationBlocker($property) : null;

        if ($blocker === null) {
            $this->moderation->applyVisibility($property, $request->user(), $request->wantsOnline());
        }

        return $blocker;
    }

    /**
     * Logement entier : son unité unique est tenue à jour avec le formulaire.
     */
    private function syncWholeUnit(Property $property, PropertyRequest $request): void
    {
        if ($request->isWholeHome()) {
            $this->wholeUnit->sync($property, $request->wholeUnitAttributes(), $request->wholeUnitEquipmentIds());
        }
    }

    /**
     * Limite d'unités de la formule du propriétaire, atteinte avant de créer l'unité d'un logement entier.
     */
    private function wholeUnitBlocker(PropertyRequest $request): ?string
    {
        return $request->user()->isOwner() ? $this->subscriptions->unitBlocker($request->user()) : null;
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
            // Logement entier
            'typesLogement' => UnitType::active()->orderBy('name')->get(),
            'equipements' => Equipment::active()->orderBy('category')->orderBy('name')->get(),
        ];
    }
}
