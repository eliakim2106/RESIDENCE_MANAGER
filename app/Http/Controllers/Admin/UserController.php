<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Comptes de la plateforme (administrateurs) : liste, fiche, suspension, rôle.
 */
class UserController extends Controller
{
    /**
     * Onglets : clé d'URL => [libellé, rôles]
     *
     * @var array<string, array{0: string, 1: list<UserRole>}>
     */
    private const TABS = [
        'tous' => ['Tous', []],
        'clients' => ['Clients', [UserRole::Client]],
        'proprietaires' => ['Propriétaires', [UserRole::Owner]],
        'administrateurs' => ['Administrateurs', [UserRole::SuperAdmin, UserRole::Admin]],
    ];

    /**
     * Filtre d'état : clé d'URL => statut
     *
     * @var array<string, UserStatus>
     */
    private const STATES = [
        'actifs' => UserStatus::Active,
        'en-attente' => UserStatus::Pending,
        'suspendus' => UserStatus::Suspended,
    ];

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $search = trim((string) $request->query('search'));
        $tab = array_key_exists((string) $request->query('statut'), self::TABS) ? (string) $request->query('statut') : 'tous';
        $stateKey = array_key_exists((string) $request->query('etat'), self::STATES) ? (string) $request->query('etat') : null;

        $query = User::query()
            ->when($stateKey, fn (Builder $query) => $query->where('status', self::STATES[$stateKey]))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('company_name', 'like', $like));
            });

        $byRole = (clone $query)->selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total', 'role');
        $counts = array_map(fn (array $tab): int => $tab[1] === []
            ? (int) $byRole->sum()
            : (int) collect($tab[1])->sum(fn (UserRole $role) => $byRole[$role->value] ?? 0), self::TABS);

        $users = $query
            ->when(self::TABS[$tab][1] !== [], fn (Builder $query) => $query->whereIn('role', self::TABS[$tab][1]))
            ->withCount(['reservations', 'properties'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.utilisateurs.index', [
            'users' => $users,
            'tabs' => array_map(fn (array $tab): string => $tab[0], self::TABS),
            'counts' => $counts,
            'tab' => $tab,
            'search' => $search,
            'state' => $stateKey,
        ]);
    }

    public function show(User $utilisateur): View
    {
        Gate::authorize('view', $utilisateur);

        $utilisateur->loadCount(['reservations', 'properties', 'reviews'])
            ->load([
                'reservations' => fn ($query) => $query->with('property')->latest('id')->limit(5),
                'properties' => fn ($query) => $query->withCount('units')->latest('id')->limit(6),
                'loginLogs' => fn ($query) => $query->latest('id')->limit(10),
            ]);

        return view('admin.utilisateurs.show', [
            'user' => $utilisateur,
            'amountPaid' => (int) $utilisateur->reservations()->sum('amount_paid'),
            'roles' => UserRole::options(),
        ]);
    }

    public function suspend(User $utilisateur): RedirectResponse
    {
        Gate::authorize('manage', $utilisateur);

        // Une session ouverte est fermée à la requête suivante (EnsureUserIsActive)
        $utilisateur->update(['status' => UserStatus::Suspended]);

        return back()->with('success', "Le compte de {$utilisateur->name} est suspendu : il ne peut plus se connecter.");
    }

    public function reactivate(User $utilisateur): RedirectResponse
    {
        Gate::authorize('manage', $utilisateur);

        $utilisateur->update(['status' => UserStatus::Active]);

        return back()->with('success', "Le compte de {$utilisateur->name} est de nouveau actif.");
    }

    public function updateRole(Request $request, User $utilisateur): RedirectResponse
    {
        Gate::authorize('changeRole', $utilisateur);

        $validated = $request->validate(['role' => ['required', Rule::enum(UserRole::class)]]);

        $utilisateur->update(['role' => UserRole::from($validated['role'])]);

        return back()->with('success', "{$utilisateur->name} est désormais « {$utilisateur->role->label()} ».");
    }
}
