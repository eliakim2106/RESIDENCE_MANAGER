<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingCycle;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * « Mon abonnement » : formule du propriétaire, factures, choix ou changement de formule.
 */
class OwnerSubscriptionController extends Controller
{
    public function __construct(private SubscriptionManager $subscriptions) {}

    public function show(Request $request): View
    {
        $owner = $request->user();
        $subscription = $owner->currentSubscription?->load('plan');

        return view('admin.abonnement.show', [
            'subscription' => $subscription,
            'plans' => SubscriptionPlan::active()->ordered()->get(),
            'invoices' => $owner->subscriptions()->exists()
                ? SubscriptionInvoice::query()->whereBelongsTo($owner)->latest('period_start')->latest('id')->get()
                : collect(),
            'usage' => [
                'properties' => $owner->properties()->count(),
                'units' => $this->subscriptions->unitCount($owner),
            ],
            'required' => SubscriptionManager::required(),
            'hadTrial' => $owner->subscriptions()->exists(),
        ]);
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'formule' => ['required', Rule::exists('subscription_plans', 'id')],
            'cycle' => ['required', Rule::enum(BillingCycle::class)],
        ]);

        try {
            $subscription = $this->subscriptions->subscribe(
                $request->user(),
                SubscriptionPlan::findOrFail($validated['formule']),
                BillingCycle::from($validated['cycle']),
            );
        } catch (WorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $subscription->onTrial()
            ? 'Votre essai gratuit « '.$subscription->plan->name.' » commence : '.$subscription->trialDaysLeft().' jours offerts.'
            : 'Formule « '.$subscription->plan->name.' » enregistrée.');
    }

    /**
     * Facture imprimable (propriétaire concerné ou administrateur).
     */
    public function invoice(Request $request, SubscriptionInvoice $facture): View
    {
        abort_unless($request->user()->isAdmin() || $facture->user_id === $request->user()->id, 403);

        $facture->load(['user', 'subscription.plan']);

        return view('admin.abonnement.invoice', ['invoice' => $facture]);
    }
}
