<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Enums\EquipmentCategory;
use App\Http\Controllers\Admin\Concerns\FiltersByStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EquipmentRequest;
use App\Models\Equipment;
use App\Support\ExcelExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EquipmentController extends Controller
{
    use FiltersByStatus;

    /**
     * Tris proposés dans la liste.
     */
    public const SORTS = ['categorie' => 'Par catégorie', 'recents' => 'Plus récents', 'nom' => 'Nom (A → Z)', 'utilisation' => 'Plus utilisés'];

    public function index(Request $request): View
    {
        [$query, $search, $sort, $category] = $this->filtered($request);
        [$counts, $statut] = $this->filterByStatus($request, $query, fn ($query) => $query->where('statut', ActiveStatus::Active));

        $equipements = $this->sorted($query->withCount(['units', 'properties']), $sort)
            ->paginate(12)
            ->withQueryString();

        $usage = Equipment::query()->withCount(['units', 'properties'])->get();
        $top = $usage->sortByDesc(fn (Equipment $equipment) => $equipment->units_count + $equipment->properties_count)->first();

        return view('admin.equipements.index', [
            'equipements' => $equipements,
            'search' => $search,
            'sort' => $sort,
            'category' => $category,
            'categories' => EquipmentCategory::cases(),
            'categoryCounts' => $usage->countBy(fn (Equipment $equipment) => $equipment->category->value),
            'counts' => $counts,
            'statut' => $statut,
            'summary' => [
                'total' => $usage->count(),
                'active' => $usage->where('statut', ActiveStatus::Active)->count(),
                'popular' => $usage->where('is_popular', true)->count(),
                'unused' => $usage->filter(fn (Equipment $equipment) => $equipment->units_count + $equipment->properties_count === 0)->count(),
                'top' => $top && $top->units_count + $top->properties_count > 0 ? $top : null,
                'max' => max(1, (int) $usage->max(fn (Equipment $equipment) => $equipment->units_count + $equipment->properties_count)),
            ],
        ]);
    }

    /**
     * Export Excel des équipements affichés (mêmes filtres que la liste).
     */
    public function export(Request $request): BinaryFileResponse
    {
        [$query, , $sort] = $this->filtered($request);
        $this->filterByStatus($request, $query, fn ($query) => $query->where('statut', ActiveStatus::Active));

        return ExcelExport::download('equipements', 'Équipements', [
            ['label' => 'Équipement', 'width' => 30],
            ['label' => 'Catégorie', 'width' => 18],
            ['label' => 'Mis en avant', 'width' => 13],
            ['label' => 'Établissements', 'type' => 'number', 'width' => 15],
            ['label' => 'Unités', 'type' => 'number'],
            ['label' => 'Statut', 'width' => 12],
        ], $this->sorted($query->withCount(['units', 'properties']), $sort)->lazy()->map(fn (Equipment $equipment): array => [
            $equipment->name,
            $equipment->category,
            $equipment->is_popular,
            (int) $equipment->properties_count,
            (int) $equipment->units_count,
            $equipment->statut,
        ]));
    }

    /**
     * @return array{0: Builder<Equipment>, 1: string, 2: string, 3: ?EquipmentCategory}
     */
    private function filtered(Request $request): array
    {
        $search = trim((string) $request->query('search'));
        $sort = array_key_exists((string) $request->query('tri'), self::SORTS) ? (string) $request->query('tri') : 'categorie';
        $category = EquipmentCategory::tryFrom((string) $request->query('categorie'));

        $query = Equipment::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($category, fn ($query) => $query->where('category', $category));

        return [$query, $search, $sort, $category];
    }

    /**
     * @param  Builder<Equipment>  $query
     * @return Builder<Equipment>
     */
    private function sorted(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'nom' => $query->orderBy('name'),
            'recents' => $query->latest('id'),
            'utilisation' => $query->orderByRaw('(units_count + properties_count) desc')->orderBy('name'),
            default => $query->orderBy('category')->orderByDesc('is_popular')->orderBy('name'),
        };
    }

    public function create(): View
    {
        return view('admin.equipements.create', ['equipement' => new Equipment]);
    }

    public function store(EquipmentRequest $request): RedirectResponse
    {
        Equipment::create($request->equipmentAttributes());

        return redirect()->route('admin.equipements.index')
            ->with('success', 'Équipement créé avec succès.');
    }

    public function edit(Equipment $equipement): View
    {
        return view('admin.equipements.edit', compact('equipement'));
    }

    public function update(EquipmentRequest $request, Equipment $equipement): RedirectResponse
    {
        $equipement->update($request->equipmentAttributes());

        return redirect()->route('admin.equipements.index')
            ->with('success', 'Modification effectuée avec succès.');
    }

    public function destroy(Equipment $equipement): RedirectResponse
    {
        $used = $equipement->properties()->count() + $equipement->units()->count();

        if ($used > 0) {
            return back()->with('error', "« {$equipement->name} » est proposé par {$used} établissement(s) ou unité(s) : désactivez-le plutôt que de le supprimer.");
        }

        $equipement->delete();

        return redirect()->route('admin.equipements.index')
            ->with('success', "« {$equipement->name} » a été supprimé.");
    }
}
