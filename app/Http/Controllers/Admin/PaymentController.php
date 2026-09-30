<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Journal des paiements : tous pour un administrateur, ceux de ses établissements pour un propriétaire.
 */
class PaymentController extends Controller
{
    /**
     * @var array<string, array{0: string, 1: ?TransactionStatus}>
     */
    private const TABS = [
        'tous' => ['Tous', null],
        'acceptes' => ['Encaissés', TransactionStatus::Accepted],
        'en-cours' => ['En cours', TransactionStatus::Pending],
        'refuses' => ['Refusés', TransactionStatus::Refused],
        'rembourses' => ['Remboursés', TransactionStatus::Refunded],
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search'));
        $tab = array_key_exists((string) $request->query('statut'), self::TABS) ? (string) $request->query('statut') : 'tous';
        $method = PaymentMethod::tryFrom((string) $request->query('moyen'));

        $query = Payment::query()
            ->unless($user->isAdmin(), fn (Builder $query) => $query->whereHas('reservation.property', fn (Builder $query) => $query->ownedBy($user)))
            ->when($method, fn (Builder $query) => $query->where('method', $method))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(fn (Builder $query) => $query
                    ->where('transaction_id', 'like', $like)
                    ->orWhere('operator_reference', 'like', $like)
                    ->orWhereHas('reservation', fn (Builder $query) => $query
                        ->where('reference', 'like', $like)
                        ->orWhere('guest_name', 'like', $like)));
            });

        $byStatus = (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $counts = collect(self::TABS)->map(fn (array $tab): int => $tab[1] === null ? (int) $byStatus->sum() : (int) ($byStatus[$tab[1]->value] ?? 0));

        $accepted = (clone $query)->where('status', TransactionStatus::Accepted);
        $totals = [
            'all' => (int) (clone $accepted)->sum('amount'),
            'month' => (int) (clone $accepted)->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'pending' => (int) (clone $query)->where('status', TransactionStatus::Pending)->sum('amount'),
        ];

        $payments = $query
            ->when(self::TABS[$tab][1], fn (Builder $query, TransactionStatus $status) => $query->where('status', $status))
            ->with(['reservation.property', 'reservation.user'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.paiements.index', [
            'payments' => $payments,
            'tabs' => array_map(fn (array $tab): string => $tab[0], self::TABS),
            'counts' => $counts,
            'tab' => $tab,
            'search' => $search,
            'method' => $method,
            'methods' => PaymentMethod::options(),
            'totals' => $totals,
        ]);
    }
}
