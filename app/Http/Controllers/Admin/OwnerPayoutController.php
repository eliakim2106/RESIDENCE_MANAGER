<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PayoutMethod;
use App\Http\Controllers\Controller;
use App\Services\PayoutLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * « Mes reversements » (propriétaire) : solde, historique et coordonnées de reversement.
 */
class OwnerPayoutController extends Controller
{
    public function __construct(private PayoutLedger $ledger) {}

    public function index(Request $request): View
    {
        $owner = $request->user()->load('currentSubscription.plan');

        return view('admin.mes-reversements.index', [
            'owner' => $owner,
            'balance' => $this->ledger->balance($owner),
            'rate' => $this->ledger->commissionRate($owner),
            'payouts' => $owner->payouts()->latest('paid_at')->latest('id')->get(),
            'methods' => PayoutMethod::options(),
        ]);
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'moyen' => ['required', Rule::enum(PayoutMethod::class)],
            'compte' => ['required', 'string', 'max:100'],
            'titulaire' => ['required', 'string', 'max:191'],
        ]);

        $request->user()->update([
            'payout_method' => $validated['moyen'],
            'payout_account' => $validated['compte'],
            'payout_holder' => $validated['titulaire'],
        ]);

        return back()->with('success', 'Coordonnées de reversement enregistrées.');
    }
}
