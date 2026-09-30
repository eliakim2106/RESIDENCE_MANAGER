<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionManager;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Suivi des abonnements des propriétaires et encaissement des factures (administrateurs).
 */
class SubscriptionController extends Controller
{
    /**
     * @var array<string, array{0: string, 1: list<SubscriptionStatus>}>
     */
    private const TABS = [
        'tous' => ['Tous', []],
        'essai' => ['En essai', [SubscriptionStatus::Trial]],
        'actifs' => ['Actifs', [SubscriptionStatus::Active]],
        'en-retard' => ['Paiement attendu', [SubscriptionStatus::PastDue]],
        'suspendus' => ['Suspendus', [SubscriptionStatus::Suspended]],
        'resilies' => ['Résiliés', [SubscriptionStatus::Cancelled]],
    ];

    public function __construct(private SubscriptionManager $subscriptions) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $tab = array_key_exists((string) $request->query('statut'), self::TABS) ? (string) $request->query('statut') : 'tous';

        // Seul l'abonnement le plus récent de chaque propriétaire compte
        $query = Subscription::query()
            ->whereIn('id', Subscription::query()->selectRaw('MAX(id)')->groupBy('user_id'))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->whereHas('user', fn (Builder $query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('company_name', 'like', $like));
            });

        $byStatus = (clone $query)->selectRaw('statut, COUNT(*) as total')->groupBy('statut')->pluck('total', 'statut');
        $counts = array_map(fn (array $tab): int => $tab[1] === []
            ? (int) $byStatus->sum()
            : (int) collect($tab[1])->sum(fn (SubscriptionStatus $status) => $byStatus[$status->value] ?? 0), self::TABS);

        $subscriptions = $query
            ->when(self::TABS[$tab][1] !== [], fn (Builder $query) => $query->whereIn('statut', self::TABS[$tab][1]))
            ->with(['user', 'plan', 'openInvoice'])
            ->withCount(['invoices as unpaid_count' => fn ($query) => $query->where('statut', InvoiceStatus::Unpaid)])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // Revenu mensuel récurrent : abonnements payants en règle, ramenés au mois
        $recurring = Subscription::query()
            ->whereIn('id', Subscription::query()->selectRaw('MAX(id)')->groupBy('user_id'))
            ->whereIn('statut', [SubscriptionStatus::Active, SubscriptionStatus::PastDue])
            ->with('plan')
            ->get()
            ->sum(fn (Subscription $subscription) => intdiv($subscription->plan->priceFor($subscription->billing_cycle), $subscription->billing_cycle->months()));

        $unpaid = SubscriptionInvoice::query()->where('statut', InvoiceStatus::Unpaid);

        return view('admin.abonnements.index', [
            'subscriptions' => $subscriptions,
            'tabs' => array_map(fn (array $tab): string => $tab[0], self::TABS),
            'counts' => $counts,
            'tab' => $tab,
            'search' => $search,
            'summary' => [
                'recurring' => $recurring,
                'unpaidAmount' => (int) (clone $unpaid)->sum('amount'),
                'unpaidCount' => (clone $unpaid)->count(),
                'withoutSubscription' => User::query()->where('role', UserRole::Owner)->whereDoesntHave('subscriptions')->count(),
            ],
            'required' => SubscriptionManager::required(),
        ]);
    }

    public function show(Subscription $abonnement): View
    {
        $abonnement->load(['user', 'plan', 'invoices' => fn ($query) => $query->latest('period_start')->latest('id'), 'invoices.recorder']);

        return view('admin.abonnements.show', [
            'subscription' => $abonnement,
            'owner' => $abonnement->user,
            'plans' => SubscriptionPlan::active()->ordered()->get(),
            'usage' => [
                'properties' => $abonnement->user->properties()->count(),
                'units' => $this->subscriptions->unitCount($abonnement->user),
            ],
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    /**
     * Abonnement offert ou souscrit pour le compte d'un propriétaire qui n'en a pas encore.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'proprietaire' => ['required', Rule::exists('users', 'id')->where('role', UserRole::Owner->value)],
            'formule' => ['required', Rule::exists('subscription_plans', 'id')],
            'cycle' => ['required', Rule::enum(BillingCycle::class)],
        ]);

        $owner = User::findOrFail($validated['proprietaire']);

        return $this->run(function () use ($owner, $validated) {
            $subscription = $this->subscriptions->subscribe($owner, SubscriptionPlan::findOrFail($validated['formule']), BillingCycle::from($validated['cycle']));

            return redirect()->route('admin.abonnements.show', $subscription)->with('success', 'Abonnement créé pour '.$owner->name.'.');
        });
    }

    public function changePlan(Request $request, Subscription $abonnement): RedirectResponse
    {
        $validated = $request->validate([
            'formule' => ['required', Rule::exists('subscription_plans', 'id')],
            'cycle' => ['required', Rule::enum(BillingCycle::class)],
        ]);

        return $this->run(function () use ($abonnement, $validated) {
            $this->subscriptions->changePlan($abonnement, SubscriptionPlan::findOrFail($validated['formule']), BillingCycle::from($validated['cycle']));

            return back()->with('success', 'Formule modifiée. Elle sera facturée à la prochaine période.');
        });
    }

    public function extendTrial(Request $request, Subscription $abonnement): RedirectResponse
    {
        $validated = $request->validate(['jours' => ['required', 'integer', 'min:1', 'max:365']]);

        return $this->run(function () use ($abonnement, $validated) {
            $this->subscriptions->extendTrial($abonnement, (int) $validated['jours']);

            return back()->with('success', 'Essai prolongé de '.$validated['jours'].' jour(s).');
        });
    }

    public function cancel(Subscription $abonnement): RedirectResponse
    {
        $this->subscriptions->cancel($abonnement);

        return back()->with('success', 'Abonnement résilié.');
    }

    public function markPaid(Request $request, SubscriptionInvoice $facture): RedirectResponse
    {
        $validated = $request->validate([
            'moyen' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        return $this->run(function () use ($request, $facture, $validated) {
            $this->subscriptions->markPaid($facture, PaymentMethod::from($validated['moyen']), $validated['reference'] ?? null, $request->user());

            return back()->with('success', 'Facture '.$facture->number.' marquée comme payée.');
        });
    }

    public function cancelInvoice(SubscriptionInvoice $facture): RedirectResponse
    {
        return $this->run(function () use ($facture) {
            $this->subscriptions->cancelInvoice($facture);

            return back()->with('success', 'Facture '.$facture->number.' annulée.');
        });
    }

    private function run(Closure $action): RedirectResponse
    {
        try {
            return $action();
        } catch (WorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }
}
