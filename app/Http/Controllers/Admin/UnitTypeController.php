<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Admin\Concerns\FiltersByStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UnitTypeRequest;
use App\Models\UnitType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitTypeController extends Controller
{
    use FiltersByStatus;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $query = UnitType::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")));

        [$counts, $statut] = $this->filterByStatus($request, $query, fn ($query) => $query->where('statut', ActiveStatus::Active));

        $types = $query
            ->withCount('units')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.types-unite.index', compact('types', 'search', 'counts', 'statut'));
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
