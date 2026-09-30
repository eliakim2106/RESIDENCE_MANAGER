<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Admin\Concerns\FiltersByStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UnitRequest;
use App\Models\Equipment;
use App\Models\Property;
use App\Models\Unit;
use App\Models\UnitType;
use App\Services\GalleryManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Les unités se créent depuis leur établissement et suivent ses droits d'accès.
 */
class UnitController extends Controller
{
    use FiltersByStatus;

    public function __construct(private GalleryManager $gallery) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $user = $request->user();

        $query = Unit::query()
            ->whereHas('property', fn ($query) => $query->unless($user->isAdmin(), fn ($query) => $query->ownedBy($user)))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('property', fn ($query) => $query->where('name', 'like', "%{$search}%"))));

        [$counts, $statut] = $this->filterByStatus($request, $query, fn ($query) => $query->where('statut', ActiveStatus::Active));

        $unites = $query
            ->with(['unitType', 'property', 'images' => fn ($query) => $query->limit(1)])
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.unites.index', compact('unites', 'search', 'counts', 'statut'));
    }

    public function create(Property $etablissement): View
    {
        Gate::authorize('update', $etablissement);

        return view('admin.unites.create', [
            'etablissement' => $etablissement,
            'unite' => new Unit,
            ...$this->formOptions(),
        ]);
    }

    public function store(UnitRequest $request, Property $etablissement): RedirectResponse
    {
        Gate::authorize('update', $etablissement);

        DB::transaction(function () use ($request, $etablissement): void {
            $unit = $etablissement->units()->create($request->unitAttributes());

            $this->syncRelations($unit, $request);
        });

        return redirect()->route('admin.unites.index')
            ->with('success', 'Unité créée avec succès.');
    }

    public function edit(Unit $unite): View
    {
        Gate::authorize('update', $unite->property);

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

        $unite->delete();

        return redirect()->route('admin.unites.index')
            ->with('success', 'Suppression effectuée avec succès.');
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
