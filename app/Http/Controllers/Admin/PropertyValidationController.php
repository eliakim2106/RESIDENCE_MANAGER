<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PropertyStatus;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\PropertyModeration;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Validation des établissements par les administrateurs : approuver, refuser, suspendre, rétablir.
 */
class PropertyValidationController extends Controller
{
    /**
     * @var array<string, array{0: string, 1: PropertyStatus}>
     */
    private const TABS = [
        'a-valider' => ['À valider', PropertyStatus::Pending],
        'publies' => ['Publiés', PropertyStatus::Published],
        'suspendus' => ['Suspendus', PropertyStatus::Suspended],
    ];

    public function __construct(private PropertyModeration $moderation) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $tab = array_key_exists((string) $request->query('statut'), self::TABS) ? (string) $request->query('statut') : 'a-valider';

        $query = Property::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('district', 'like', $like)
                    ->orWhereHas('city', fn (Builder $query) => $query->where('name', 'like', $like))
                    ->orWhereHas('owner', fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like)));
            });

        $byStatus = (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $counts = array_map(fn (array $tab): int => (int) ($byStatus[$tab[1]->value] ?? 0), self::TABS);

        $status = self::TABS[$tab][1];

        $properties = $query
            ->where('status', $status)
            ->with(['owner', 'city', 'propertyType', 'coverImage', 'moderator'])
            ->withCount(['units', 'images'])
            // Les plus anciennes demandes d'abord : premier arrivé, premier servi
            ->when($status === PropertyStatus::Pending, fn (Builder $query) => $query->orderBy('submitted_at'), fn (Builder $query) => $query->latest('moderated_at'))
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.validations.index', [
            'properties' => $properties,
            'tabs' => array_map(fn (array $tab): string => $tab[0], self::TABS),
            'counts' => $counts,
            'tab' => $tab,
            'search' => $search,
        ]);
    }

    public function approve(Request $request, Property $etablissement): RedirectResponse
    {
        return $this->run(fn () => $this->moderation->approve($etablissement, $request->user()), "« {$etablissement->name} » est publié.");
    }

    public function reject(Request $request, Property $etablissement): RedirectResponse
    {
        $reason = $this->reason($request);

        return $this->run(fn () => $this->moderation->reject($etablissement, $request->user(), $reason), "« {$etablissement->name} » est renvoyé au propriétaire.");
    }

    public function suspend(Request $request, Property $etablissement): RedirectResponse
    {
        $reason = $this->reason($request);

        return $this->run(fn () => $this->moderation->suspend($etablissement, $request->user(), $reason), "« {$etablissement->name} » est suspendu.");
    }

    public function reinstate(Request $request, Property $etablissement): RedirectResponse
    {
        return $this->run(fn () => $this->moderation->reinstate($etablissement, $request->user()), "« {$etablissement->name} » est de nouveau publié.");
    }

    /*
    |--------------------------------------------------------------------------
    | OUTILS
    |--------------------------------------------------------------------------
    */

    private function reason(Request $request): string
    {
        $validated = $request->validate(
            ['motif' => ['required', 'string', 'min:10', 'max:1000']],
            [
                'motif.required' => 'Indiquez le motif : il sera transmis au propriétaire.',
                'motif.min' => 'Le motif doit être un peu plus détaillé (10 caractères minimum).',
            ],
        );

        return trim($validated['motif']);
    }

    private function run(Closure $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (WorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $success);
    }
}
