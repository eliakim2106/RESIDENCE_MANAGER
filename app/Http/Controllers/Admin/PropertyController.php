<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Admin\Concerns\FiltersByStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PropertyRequest;
use App\Models\City;
use App\Models\Property;
use App\Models\PropertyType;
use App\Services\GalleryManager;
use App\Services\PropertyModeration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PropertyController extends Controller
{
    use FiltersByStatus;

    public function __construct(private GalleryManager $gallery, private PropertyModeration $moderation) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $user = $request->user();

        $query = Property::query()
            ->unless($user->isAdmin(), fn ($query) => $query->ownedBy($user))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('district', 'like', "%{$search}%")
                ->orWhere('neighborhood', 'like', "%{$search}%")
                ->orWhereHas('city', fn ($query) => $query->where('name', 'like', "%{$search}%"))));

        // Actif = publié sur le site
        [$counts, $statut] = $this->filterByStatus($request, $query, fn ($query) => $query->where('status', PropertyStatus::Published));

        $etablissements = $query
            ->with(['propertyType', 'city', 'coverImage'])
            ->withCount('units')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.etablissements.index', compact('etablissements', 'search', 'counts', 'statut'));
    }

    public function create(): View
    {
        Gate::authorize('create', Property::class);

        return view('admin.etablissements.create', [
            'etablissement' => new Property,
            ...$this->formOptions(),
        ]);
    }

    public function store(PropertyRequest $request): RedirectResponse
    {
        Gate::authorize('create', Property::class);

        $property = DB::transaction(function () use ($request): Property {
            $property = Property::create([
                ...$request->propertyAttributes(),
                'owner_id' => $request->user()->id,
                // Toujours créé en brouillon : la publication ou la soumission se fait ensuite (PropertyModeration)
                'status' => PropertyStatus::Draft,
            ]);

            $this->syncMedia($property, $request);
            $this->moderation->applyVisibility($property, $request->user(), $request->wantsOnline());

            return $property;
        });

        return redirect()->route('admin.etablissements.index')
            ->with('success', $property->isPending()
                ? 'Établissement créé et envoyé pour validation. Il sera publié dès qu’un administrateur l’aura approuvé.'
                : 'Établissement créé avec succès.');
    }

    public function edit(Property $etablissement): View
    {
        Gate::authorize('update', $etablissement);

        $etablissement->load(['images' => fn ($query) => $query->orderByDesc('is_cover')->orderBy('position')]);

        return view('admin.etablissements.edit', [
            'etablissement' => $etablissement,
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

        return redirect()->route('admin.etablissements.index')
            ->with('success', $etablissement->isPending() && ! $wasPending
                ? 'Établissement envoyé pour validation. Il sera publié dès qu’un administrateur l’aura approuvé.'
                : 'Établissement modifié avec succès.');
    }

    public function destroy(Property $etablissement): RedirectResponse
    {
        Gate::authorize('delete', $etablissement);

        $etablissement->delete();

        return redirect()->route('admin.etablissements.index')
            ->with('success', 'Suppression effectuée avec succès.');
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
        ];
    }
}
