<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EquipmentRequest;
use App\Models\Equipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $equipements = Equipment::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.equipements.index', compact('equipements', 'search'));
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
        $equipement->delete();

        return redirect()->route('admin.equipements.index')
            ->with('success', 'Suppression effectuée avec succès.');
    }
}
