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
        'refuses' => ['Refusés', PropertyStatus::Draft],
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

        $counts = collect(self::TABS)->map(fn (array $definition, string $key): int => $this->forTab(clone $query, $key)->count())->all();

        $status = self::TABS[$tab][1];

        $properties = $this->forTab($query, $tab)
            ->with(['owner', 'city', 'propertyType', 'coverImage', 'moderator'])
            ->withCount(['units', 'images'])
            // Les plus anciennes demandes d'abord : premier arrivé, premier servi
            ->when($status === PropertyStatus::Pending, fn (Builder $query) => $query->orderBy('submitted_at'), fn (Builder $query) => $query->latest('moderated_at'))
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        $oldest = Property::query()->where('statut', PropertyStatus::Pending)->min('submitted_at');

        return view('admin.validations.index', [
            'properties' => $properties,
            'tabs' => array_map(fn (array $tab): string => $tab[0], self::TABS),
            'counts' => $counts,
            'tab' => $tab,
            'search' => $search,
            'summary' => [
                'pending' => Property::query()->where('statut', PropertyStatus::Pending)->count(),
                'oldestDays' => $oldest ? (int) now()->diffInDays($oldest, true) : null,
                'decisionsThisMonth' => Property::query()->where('moderated_at', '>=', now()->startOfMonth())->count(),
                'published' => Property::query()->where('statut', PropertyStatus::Published)->count(),
            ],
            // Dernières décisions, affichées quand rien n'attend
            'recentDecisions' => Property::query()
                ->whereNotNull('moderated_at')
                ->with(['owner', 'moderator'])
                ->latest('moderated_at')
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * Restreint la requête à un onglet (« Refusés » : brouillon avec un motif de refus).
     *
     * @param  Builder<Property>  $query
     * @return Builder<Property>
     */
    private function forTab(Builder $query, string $tab): Builder
    {
        return match ($tab) {
            'refuses' => $query->where('statut', PropertyStatus::Draft)->whereNotNull('moderation_note')->where('moderation_note', '!=', ''),
            default => $query->where('statut', self::TABS[$tab][1]),
        };
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
