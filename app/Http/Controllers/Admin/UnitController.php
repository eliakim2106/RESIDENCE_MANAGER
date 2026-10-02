<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UnitRequest;
use App\Models\Equipment;
use App\Models\Property;
use App\Models\Unit;
use App\Models\UnitType;
use App\Services\GalleryManager;
use App\Services\SubscriptionManager;
use App\Services\UnitListing;
use App\Support\ExcelExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Les unités se créent depuis leur établissement et suivent ses droits d'accès.
 */
class UnitController extends Controller
{
    public function __construct(private GalleryManager $gallery, private SubscriptionManager $subscriptions) {}

    public function index(Request $request): View
    {
        $listing = new UnitListing($request->user(), $request);

        return view('admin.unites.index', [
            'listing' => $listing,
            'unites' => $listing->paginate(),
            'counts' => $listing->counts(),
            'summary' => $listing->summary(),
        ]);
    }

    /**
     * Export Excel des unités affichées (mêmes filtres que la liste).
     */
    public function export(Request $request): BinaryFileResponse
    {
        $listing = new UnitListing($request->user(), $request);

        return ExcelExport::download('unites', 'Unités', [
            ['label' => 'Unité', 'width' => 30],
            ['label' => 'Type', 'width' => 20],
            ['label' => 'Établissement', 'width' => 28],
            ['label' => 'Ville', 'width' => 16],
            ['label' => 'Adultes max.', 'type' => 'number'],
            ['label' => 'Enfants max.', 'type' => 'number'],
            ['label' => 'Exemplaires', 'type' => 'number'],
            ['label' => 'Prix / nuit', 'type' => 'money'],
            ['label' => 'Prix promo', 'type' => 'money'],
            ['label' => 'Frais de ménage', 'type' => 'money'],
            ['label' => 'Nuits min.', 'type' => 'number'],
            ['label' => 'Réservations à venir', 'type' => 'number', 'width' => 14],
            ['label' => 'Statut', 'width' => 12],
            ['label' => 'Créée le', 'type' => 'date'],
        ], $listing->export()->map(fn (Unit $unit): array => [
            $unit->name,
            $unit->unitType?->name,
            $unit->property?->name,
            $unit->property?->city?->name,
            $unit->max_adults,
            $unit->max_children,
            $unit->quantity,
            $unit->base_price,
            $unit->promo_price,
            $unit->cleaning_fee,
            $unit->min_nights,
            (int) $unit->upcoming_count,
            $unit->statut,
            $unit->created_at,
        ]));
    }

    public function create(Request $request, Property $etablissement): View|RedirectResponse
    {
        Gate::authorize('update', $etablissement);

        if ($redirect = $this->wholeHomeRedirect($etablissement)) {
            return $redirect;
        }

        if ($request->user()->isOwner() && ($blocker = $this->subscriptions->unitBlocker($request->user()))) {
            return redirect()->route('admin.abonnement.show')->with('error', $blocker);
        }

        return view('admin.unites.create', [
            'etablissement' => $etablissement,
            'unite' => new Unit,
            ...$this->formOptions(),
        ]);
    }

    public function store(UnitRequest $request, Property $etablissement): RedirectResponse
    {
        Gate::authorize('update', $etablissement);

        if ($redirect = $this->wholeHomeRedirect($etablissement)) {
            return $redirect;
        }

        if ($request->user()->isOwner() && ($blocker = $this->subscriptions->unitBlocker($request->user()))) {
            return redirect()->route('admin.abonnement.show')->with('error', $blocker);
        }

        DB::transaction(function () use ($request, $etablissement): void {
            $unit = $etablissement->units()->create($request->unitAttributes());

            $this->syncRelations($unit, $request);
        });

        return redirect()->route('admin.unites.index')
            ->with('success', 'Unité créée avec succès.');
    }

    public function edit(Unit $unite): View|RedirectResponse
    {
        Gate::authorize('update', $unite->property);

        if (! $unite->property->manages_units) {
            return $this->toWholeHomeForm($unite->property);
        }

        $unite->load(['images' => fn ($query) => $query->orderBy('position'), 'equipments']);

        return view('admin.unites.edit', [
            'etablissement' => $unite->property,
            'unite' => $unite,
            ...$this->formOptions(),
        ]);
    }

    public function update(UnitRequest $request, Unit $unite): RedirectResponse
    {
        Gate::authorize('update', $unite->property);

        if (! $unite->property->manages_units) {
            return $this->toWholeHomeForm($unite->property);
        }

        DB::transaction(function () use ($request, $unite): void {
            $unite->update($request->unitAttributes());

            $this->syncRelations($unite, $request);
        });

        return redirect()->route('admin.unites.index')
            ->with('success', 'Unité modifiée avec succès.');
    }

    public function destroy(Unit $unite): RedirectResponse
    {
        Gate::authorize('update', $unite->property);

        // Des clients ont encore un séjour à venir dans cette unité : on ne la retire pas
        $active = $unite->reservationUnits()
            ->whereHas('reservation', fn ($query) => $query
                ->whereIn('statut', [ReservationStatus::Pending, ReservationStatus::Confirmed])
                ->whereDate('check_out', '>=', now()->toDateString()))
            ->count();

        if ($active > 0) {
            return back()->with('error', "« {$unite->name} » figure dans {$active} réservation".($active > 1 ? 's' : '').' en attente ou à venir. Désactivez-la plutôt : elle ne sera plus proposée aux clients.');
        }

        $unite->delete();

        return redirect()->route('admin.unites.index')
            ->with('success', "« {$unite->name} » a été supprimée.");
    }

    /**
     * Logement entier (sans gestion des unités) : une seule unité, réglée dans le formulaire de l'établissement.
     */
    private function wholeHomeRedirect(Property $property): ?RedirectResponse
    {
        if ($property->manages_units || ! $property->units()->exists()) {
            return null;
        }

        return $this->toWholeHomeForm($property, "« {$property->name} » est un logement entier : il n’a qu’une unité. "
            .'Activez « Gestion des unités » pour proposer plusieurs chambres ou logements.');
    }

    private function toWholeHomeForm(Property $property, ?string $message = null): RedirectResponse
    {
        return redirect()->route('admin.etablissements.edit', ['etablissement' => $property, 'etape' => 'accueil'])
            ->with('success', $message ?? "« {$property->name} » est un logement entier : son prix, sa capacité et ses équipements se règlent ici, à la rubrique « Votre logement ».");
    }

    private function syncRelations(Unit $unit, UnitRequest $request): void
    {
        $unit->equipments()->sync($request->equipmentIds());

        $this->gallery->sync(
            fn () => $unit->images(),
            "units/{$unit->id}",
            $request->deletedGalleryIds(),
            $request->file('images', []),
            $request->input('gallery_cover'),
            hasCoverColumn: false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'typesUnite' => UnitType::active()->orderBy('name')->get(),
            'equipements' => Equipment::active()->orderBy('category')->orderBy('name')->get(),
        ];
    }
}
