<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Admin\Concerns\FiltersByStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PropertyTypeRequest;
use App\Models\PropertyType;
use App\Support\ExcelExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PropertyTypeController extends Controller
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

        $types = $this->sorted($query->withCount('properties'), $sort)
            ->paginate(12)
            ->withQueryString();

        $top = PropertyType::query()->withCount('properties')->orderByDesc('properties_count')->first();

        return view('admin.types-etablissement.index', [
            'types' => $types,
            'search' => $search,
            'sort' => $sort,
            'counts' => $counts,
            'statut' => $statut,
            'summary' => [
                'total' => PropertyType::query()->count(),
                'active' => PropertyType::query()->where('statut', ActiveStatus::Active)->count(),
                'used' => PropertyType::query()->has('properties')->count(),
                'top' => $top?->properties_count > 0 ? $top : null,
                'max' => max(1, (int) $top?->properties_count),
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

        return ExcelExport::download('types-etablissement', 'Types d’établissement', [
            ['label' => 'Type', 'width' => 26],
            ['label' => 'Description', 'width' => 60],
            ['label' => 'Établissements', 'type' => 'number'],
            ['label' => 'Statut', 'width' => 12],
            ['label' => 'Créé le', 'type' => 'date'],
        ], $this->sorted($query->withCount('properties'), $sort)->lazy()->map(fn (PropertyType $type): array => [
            $type->name,
            $type->description,
            (int) $type->properties_count,
            $type->statut,
            $type->created_at,
        ]));
    }

    /**
     * @return array{0: Builder<PropertyType>, 1: string, 2: string}
     */
    private function filtered(Request $request): array
    {
        $search = trim((string) $request->query('search'));
        $sort = array_key_exists((string) $request->query('tri'), self::SORTS) ? (string) $request->query('tri') : 'recents';
        $like = '%'.addcslashes($search, '%_\\').'%';

        $query = PropertyType::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', $like)
                ->orWhere('description', 'like', $like)));

        return [$query, $search, $sort];
    }

    /**
     * @param  Builder<PropertyType>  $query
     * @return Builder<PropertyType>
     */
    private function sorted(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'nom' => $query->orderBy('name'),
            'utilisation' => $query->orderByDesc('properties_count')->orderBy('name'),
            default => $query->latest('id'),
        };
    }

    public function create(): View
    {
        return view('admin.types-etablissement.create', ['type' => new PropertyType]);
    }

    public function store(PropertyTypeRequest $request): RedirectResponse
    {
        PropertyType::create($request->typeAttributes());

        return redirect()->route('admin.types-etablissement.index')
            ->with('success', "Type d'établissement créé avec succès.");
    }

    public function edit(PropertyType $type): View
    {
        return view('admin.types-etablissement.edit', compact('type'));
    }

    public function update(PropertyTypeRequest $request, PropertyType $type): RedirectResponse
    {
        $type->update($request->typeAttributes());

        return redirect()->route('admin.types-etablissement.index')
            ->with('success', 'Modification effectuée avec succès.');
    }

    public function destroy(PropertyType $type): RedirectResponse
    {
        $count = $type->properties()->withTrashed()->count();

        if ($count > 0) {
            return back()->with('error', "Ce type est utilisé par {$count} établissement(s) : désactivez-le plutôt que de le supprimer.");
        }

        $type->delete();

        return redirect()->route('admin.types-etablissement.index')
            ->with('success', 'Suppression effectuée avec succès.');
    }
}
