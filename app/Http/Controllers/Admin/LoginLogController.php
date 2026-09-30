<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Journal des connexions (administrateurs) : tentatives réussies et échouées, pour repérer un accès suspect.
 */
class LoginLogController extends Controller
{
    /**
     * @var array<string, array{0: string, 1: ?bool}>
     */
    private const TABS = [
        'toutes' => ['Toutes', null],
        'reussies' => ['Réussies', true],
        'echouees' => ['Échouées', false],
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $tab = array_key_exists((string) $request->query('statut'), self::TABS) ? (string) $request->query('statut') : 'toutes';

        $query = LoginLog::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(fn (Builder $query) => $query
                    ->where('email', 'like', $like)
                    ->orWhere('ip_address', 'like', $like)
                    ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', $like)));
            });

        $bySuccess = (clone $query)->selectRaw('successful, COUNT(*) as total')->groupBy('successful')->pluck('total', 'successful');
        $counts = array_map(fn (array $tab): int => $tab[1] === null
            ? (int) $bySuccess->sum()
            : (int) ($bySuccess[(int) $tab[1]] ?? 0), self::TABS);

        // Échecs des dernières 24 h : signal d'alerte en tête de page
        $recentFailures = LoginLog::query()->where('successful', false)->where('created_at', '>=', now()->subDay())->count();

        $logs = $query
            ->when(self::TABS[$tab][1] !== null, fn (Builder $query) => $query->where('successful', self::TABS[$tab][1]))
            ->with('user')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.utilisateurs.connexions', [
            'logs' => $logs,
            'tabs' => array_map(fn (array $tab): string => $tab[0], self::TABS),
            'counts' => $counts,
            'tab' => $tab,
            'search' => $search,
            'recentFailures' => $recentFailures,
        ]);
    }
}
