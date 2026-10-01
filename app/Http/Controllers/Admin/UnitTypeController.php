<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Admin\Concerns\FiltersByStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UnitTypeRequest;
use App\Models\UnitType;
use App\Support\ExcelExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UnitTypeController extends Controller
{
    use FiltersByStatus;

    /**
     * Tris proposés dans la liste.
     */
    public const SORTS = ['recents' => 'Plus récents', 'nom' => 'Nom (A → Z)', 'utilisation' => 'Plus utilisés'];

    public function index(Request $request): View
    {
        [$query, $search, $sort] = $this->filtered($request);
        [$counts, $statut] = $this->filterByStatus($request, $query, fn ($query) => $query->where('statut', ActiveStatus::Active));

        $types = $this->sorted($query->withCount('units'), $sort)
            ->paginate(12)
            ->withQueryString();

        $top = UnitType::query()->withCount('units')->orderByDesc('units_count')->first();

        return view('admin.types-unite.index', [
            'types' => $types,
            'search' => $search,
            'sort' => $sort,
            'counts' => $counts,
            'statut' => $statut,
            'summary' => [
                'total' => UnitType::query()->count(),
                'active' => UnitType::query()->where('statut', ActiveStatus::Active)->count(),
                'used' => UnitType::query()->has('units')->count(),
                'top' => $top?->units_count > 0 ? $top : null,
                'max' => max(1, (int) $top?->units_count),
            ],
        ]);
    }

    /**
     * Export Excel des types affichés (mêmes filtres que la liste).
     */
    public function export(Request $request): BinaryFileResponse
    {
        [$query, , $sort] = $this->filtered($request);
        $this->filterByStatus($request, $query, fn ($query) => $query->where('statut', ActiveStatus::Active));

        return ExcelExport::download('types-unite', 'Types d’unité', [
            ['label' => 'Type', 'width' => 26],
            ['label' => 'Description', 'width' => 60],
            ['label' => 'Unités', 'type' => 'number'],
            ['label' => 'Statut', 'width' => 12],
            ['label' => 'Créé le', 'type' => 'date'],
        ], $this->sorted($query->withCount('units'), $sort)->lazy()->map(fn (UnitType $type): array => [
            $type->name,
            $type->description,
            (int) $type->units_count,
            $type->statut,
            $type->created_at,
        ]));
    }

    /**
     * @return array{0: Builder<UnitType>, 1: string, 2: string}
     */
    private function filtered(Request $request): array
    {
        $search = trim((string) $request->query('search'));
        $sort = array_key_exists((string) $request->query('tri'), self::SORTS) ? (string) $request->query('tri') : 'recents';
        $like = '%'.addcslashes($search, '%_\\').'%';

        $query = UnitType::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', $like)
                ->orWhere('description', 'like', $like)));

        return [$query, $search, $sort];
    }

    /**
     * @param  Builder<UnitType>  $query
     * @return Builder<UnitType>
     */
    private function sorted(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'nom' => $query->orderBy('name'),
            'utilisation' => $query->orderByDesc('units_count')->orderBy('name'),
            default => $query->latest('id'),
        };
    }

    public function create(): View
    {
        return view('admin.types-unite.create', ['type' => new UnitType]);
    }

    public function store(UnitTypeRequest $request): RedirectResponse
    {
        UnitType::create($request->typeAttributes());

        return redirect()->route('admin.types-unite.index')
            ->with('success', "Type d'unité créé avec succès.");
    }

    public function edit(UnitType $type): View
    {
        return view('admin.types-unite.edit', compact('type'));
    }

    public function update(UnitTypeRequest $request, UnitType $type): RedirectResponse
    {
        $type->update($request->typeAttributes());

        return redirect()->route('admin.types-unite.index')
            ->with('success', 'Modification effectuée avec succès.');
    }

    public function destroy(UnitType $type): RedirectResponse
    {
        $count = $type->units()->withTrashed()->count();

        if ($count > 0) {
            return back()->with('error', "Ce type est utilisé par {$count} unité(s) : désactivez-le plutôt que de le supprimer.");
        }

        $type->delete();

        return redirect()->route('admin.types-unite.index')
            ->with('success', 'Suppression effectuée avec succès.');
    }
}
