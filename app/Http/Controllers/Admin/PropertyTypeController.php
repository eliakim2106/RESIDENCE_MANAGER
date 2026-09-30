<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersByStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PropertyTypeRequest;
use App\Models\PropertyType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyTypeController extends Controller
{
    use FiltersByStatus;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $query = PropertyType::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")));

        [$counts, $statut] = $this->filterByStatus($request, $query, fn ($query) => $query->where('is_active', true));

        $types = $query
            ->withCount('properties')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.types-etablissement.index', compact('types', 'search', 'counts', 'statut'));
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
