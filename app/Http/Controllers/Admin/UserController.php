<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ExcelExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        'non-confirmes' => UserStatus::Active, // filtré sur l'email non confirmé (voir filtered())
    ];

    /**
     * Tris proposés dans la liste.
     */
    public const SORTS = ['recents' => 'Inscrits récemment', 'nom' => 'Nom (A → Z)', 'connexion' => 'Dernière connexion', 'activite' => 'Plus actifs'];

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        [$query, $search, $tab, $stateKey, $sort] = $this->filtered($request);

        $byRole = (clone $query)->selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total', 'role');
        $counts = array_map(fn (array $tab): int => $tab[1] === []
            ? (int) $byRole->sum()
            : (int) collect($tab[1])->sum(fn (UserRole $role) => $byRole[$role->value] ?? 0), self::TABS);

        $users = $this->sorted($this->forTab($query, $tab), $sort)
            ->paginate(15)
            ->withQueryString();

        return view('admin.utilisateurs.index', [
            'users' => $users,
            'tabs' => array_map(fn (array $tab): string => $tab[0], self::TABS),
            'counts' => $counts,
            'tab' => $tab,
            'search' => $search,
            'state' => $stateKey,
            'sort' => $sort,
            'summary' => [
                'clients' => User::query()->where('role', UserRole::Client)->count(),
                'owners' => User::query()->where('role', UserRole::Owner)->count(),
                'newThisMonth' => User::query()->where('created_at', '>=', now()->startOfMonth())->count(),
                'toWatch' => User::query()->where(fn (Builder $query) => $query->whereNull('email_verified_at')->orWhere('statut', '!=', UserStatus::Active))->count(),
            ],
        ]);
    }

    /**
     * Export Excel des comptes affichés (mêmes onglet, filtres et tri que la liste).
     */
    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', User::class);

        [$query, , $tab, , $sort] = $this->filtered($request);

        return ExcelExport::download('utilisateurs', 'Utilisateurs', [
            ['label' => 'Nom', 'width' => 26],
            ['label' => 'Email', 'width' => 30],
            ['label' => 'Téléphone', 'width' => 20],
            ['label' => 'Rôle', 'width' => 18],
            ['label' => 'Entreprise', 'width' => 22],
            ['label' => 'Ville', 'width' => 16],
            ['label' => 'Pays', 'width' => 16],
            ['label' => 'Réservations', 'type' => 'number'],
            ['label' => 'Établissements', 'type' => 'number', 'width' => 14],
            ['label' => 'Statut', 'width' => 12],
            ['label' => 'Email confirmé', 'width' => 14],
            ['label' => 'Dernière connexion', 'type' => 'datetime'],
            ['label' => 'Inscrit le', 'type' => 'date'],
        ], $this->sorted($this->forTab($query, $tab), $sort)->lazy()->map(fn (User $user): array => [
            $user->name,
            $user->email,
            $user->formattedPhone(),
            $user->role,
            $user->company_name,
            $user->city,
            $user->country,
            (int) $user->reservations_count,
            (int) $user->properties_count,
            $user->statut,
            $user->hasVerifiedEmail(),
            $user->last_login_at,
            $user->created_at,
        ]));
    }

    /**
     * Requête de la liste : recherche et filtre d'état (l'onglet de rôle est appliqué à part, pour les compteurs).
     *
     * @return array{0: Builder<User>, 1: string, 2: string, 3: ?string, 4: string}
     */
    private function filtered(Request $request): array
    {
        $search = trim((string) $request->query('search'));
        $tab = array_key_exists((string) $request->query('statut'), self::TABS) ? (string) $request->query('statut') : 'tous';
        $stateKey = array_key_exists((string) $request->query('etat'), self::STATES) ? (string) $request->query('etat') : null;
        $sort = array_key_exists((string) $request->query('tri'), self::SORTS) ? (string) $request->query('tri') : 'recents';

        $query = User::query()
            ->when($stateKey === 'non-confirmes', fn (Builder $query) => $query->whereNull('email_verified_at'))
            ->when($stateKey && $stateKey !== 'non-confirmes', fn (Builder $query) => $query->where('statut', self::STATES[$stateKey]))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $digits = preg_replace('/\D/', '', $search);

                $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('company_name', 'like', $like)
                    ->when(strlen($digits) >= 4, fn (Builder $query) => $query->orWhere('phone', 'like', '%'.$digits.'%')));
            });

        return [$query, $search, $tab, $stateKey, $sort];
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    private function forTab(Builder $query, string $tab): Builder
    {
        return $query
            ->when(self::TABS[$tab][1] !== [], fn (Builder $query) => $query->whereIn('role', self::TABS[$tab][1]))
            ->withCount(['reservations', 'properties']);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    private function sorted(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'nom' => $query->orderBy('name'),
            'connexion' => $query->orderByDesc('last_login_at'),
            'activite' => $query->orderByRaw('(reservations_count + properties_count) desc'),
            default => $query->latest('id'),
        };
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
        $utilisateur->update(['statut' => UserStatus::Suspended]);

        return back()->with('success', "Le compte de {$utilisateur->name} est suspendu : il ne peut plus se connecter.");
    }

    public function reactivate(User $utilisateur): RedirectResponse
    {
        Gate::authorize('manage', $utilisateur);

        $utilisateur->update(['statut' => UserStatus::Active]);

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
