<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubscriptionPlanRequest;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Formules d'abonnement et réglages des abonnements (administrateurs).
 */
class SubscriptionPlanController extends Controller
{
    public function index(): View
    {
        return view('admin.formules.index', [
            'plans' => SubscriptionPlan::query()
                ->ordered()
                ->withCount(['subscriptions as subscribers_count' => fn ($query) => $query->whereIn('statut', SubscriptionStatus::goodStanding())])
                ->get(),
            'required' => SubscriptionManager::required(),
            'graceDays' => SubscriptionManager::graceDays(),
        ]);
    }

    public function create(): View
    {
        return view('admin.formules.create', ['plan' => new SubscriptionPlan(['trial_days' => 30, 'commission_rate' => 0])]);
    }

    public function store(SubscriptionPlanRequest $request): RedirectResponse
    {
        SubscriptionPlan::create($request->planAttributes());

        return redirect()->route('admin.formules.index')->with('success', 'Formule créée.');
    }

    public function edit(SubscriptionPlan $formule): View
    {
        return view('admin.formules.edit', ['plan' => $formule]);
    }

    public function update(SubscriptionPlanRequest $request, SubscriptionPlan $formule): RedirectResponse
    {
        $formule->update($request->planAttributes());

        return redirect()->route('admin.formules.index')->with('success', 'Formule modifiée. Les nouveaux prix s’appliquent aux prochaines factures.');
    }

    public function destroy(SubscriptionPlan $formule): RedirectResponse
    {
        if ($formule->subscriptions()->exists()) {
            return back()->with('error', 'Des propriétaires ont souscrit cette formule : désactivez-la plutôt que de la supprimer.');
        }

        $formule->delete();

        return redirect()->route('admin.formules.index')->with('success', 'Formule supprimée.');
    }

    /**
     * Abonnement obligatoire et délai de grâce.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'obligatoire' => ['nullable', 'boolean'],
            'delai_grace' => ['required', 'integer', 'min:0', 'max:60'],
        ], [
            'delai_grace.required' => 'Indiquez le délai de grâce, en jours.',
        ]);

        Setting::set(SubscriptionManager::SETTING_REQUIRED, $request->boolean('obligatoire') ? '1' : '0', 'abonnements');
        Setting::set(SubscriptionManager::SETTING_GRACE_DAYS, (string) $validated['delai_grace'], 'abonnements');

        return back()->with('success', 'Réglages des abonnements enregistrés.');
    }
}
