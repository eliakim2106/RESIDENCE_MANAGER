<?php

namespace App\Services;

use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\SubscriptionStatus;
use App\Exceptions\WorkflowException;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\AdminSubscriptionAlert;
use App\Notifications\SubscriptionUpdated;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Abonnements des propriétaires.
 *
 * Les comptes exemptés (comptes de démonstration) échappent à tout cela.
 *
 * Essai gratuit → facture à la fin de l'essai (statut « paiement attendu ») → actif une fois payée.
 * À chaque fin de période, une nouvelle facture est émise ; sans paiement à l'échéance
 * (période + délai de grâce), l'abonnement est suspendu et, si l'abonnement est obligatoire,
 * les établissements quittent le site jusqu'au paiement.
 */
class SubscriptionManager
{
    public const SETTING_REQUIRED = 'subscriptions.required';

    public const SETTING_GRACE_DAYS = 'subscriptions.grace_days';

    /**
     * Jours avant l'échéance où le propriétaire reçoit un rappel.
     */
    public const REMINDER_DAYS = 3;

    /*
    |--------------------------------------------------------------------------
    | RÉGLAGES
    |--------------------------------------------------------------------------
    */

    /**
     * Abonnement obligatoire pour rester en ligne et ajouter des établissements.
     */
    public static function required(): bool
    {
        return (bool) Setting::get(self::SETTING_REQUIRED, false);
    }

    /**
     * Jours laissés pour payer une facture avant la suspension.
     */
    public static function graceDays(): int
    {
        return max(0, (int) Setting::get(self::SETTING_GRACE_DAYS, 7));
    }

    /*
    |--------------------------------------------------------------------------
    | SOUSCRIPTION
    |--------------------------------------------------------------------------
    */

    /**
     * Souscrit une formule. Premier abonnement d'un propriétaire : essai gratuit si la formule en prévoit un.
     * S'il a déjà un abonnement en cours, c'est un changement de formule.
     */
    public function subscribe(User $owner, SubscriptionPlan $plan, BillingCycle $cycle): Subscription
    {
        if (! $owner->isOwner()) {
            throw new WorkflowException('Seul un propriétaire peut souscrire un abonnement.');
        }

        if (! $plan->isActive()) {
            throw new WorkflowException('Cette formule n’est plus proposée.');
        }

        if ($cycle === BillingCycle::Yearly && ! $plan->offersYearly()) {
            $cycle = BillingCycle::Monthly;
        }

        $current = $owner->currentSubscription;

        if ($current && $current->statut !== SubscriptionStatus::Cancelled) {
            return $this->changePlan($current, $plan, $cycle);
        }

        $this->assertFits($owner, $plan);

        // L'abonnement en cours change : la relation déjà chargée n'est plus à jour
        $owner->unsetRelation('currentSubscription');

        return DB::transaction(function () use ($owner, $plan, $cycle): Subscription {
            $firstTime = ! $owner->subscriptions()->exists();

            if ($firstTime && $plan->trial_days > 0) {
                $subscription = $owner->subscriptions()->create([
                    'subscription_plan_id' => $plan->id,
                    'billing_cycle' => $cycle,
                    'statut' => SubscriptionStatus::Trial,
                    'trial_ends_at' => now()->addDays($plan->trial_days),
                ]);

                $owner->notify(new SubscriptionUpdated($subscription, SubscriptionUpdated::TRIAL_STARTED));

                return $subscription;
            }

            $subscription = $owner->subscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'billing_cycle' => $cycle,
                'statut' => SubscriptionStatus::PastDue,
            ]);

            $this->issueInvoice($subscription, CarbonImmutable::today());

            return $subscription;
        });
    }

    /**
     * Nouvelle formule ou nouveau cycle : appliqués dès maintenant, facturés à la prochaine période.
     */
    public function changePlan(Subscription $subscription, SubscriptionPlan $plan, BillingCycle $cycle): Subscription
    {
        $this->assertFits($subscription->user, $plan);

        $subscription->update([
            'subscription_plan_id' => $plan->id,
            'billing_cycle' => $cycle,
        ]);

        return $subscription->refresh();
    }

    public function cancel(Subscription $subscription): void
    {
        DB::transaction(function () use ($subscription): void {
            $subscription->invoices()->where('statut', InvoiceStatus::Unpaid)->update(['statut' => InvoiceStatus::Cancelled]);
            $subscription->update(['statut' => SubscriptionStatus::Cancelled, 'cancelled_at' => now()]);
        });
    }

    /**
     * Prolonge l'essai (geste commercial de l'administration).
     */
    public function extendTrial(Subscription $subscription, int $days): void
    {
        if (! $subscription->onTrial()) {
            throw new WorkflowException('Seul un abonnement en essai peut être prolongé.');
        }

        $subscription->update(['trial_ends_at' => ($subscription->trial_ends_at ?? now())->addDays($days)]);
    }

    /*
    |--------------------------------------------------------------------------
    | FACTURES
    |--------------------------------------------------------------------------
    */

    /**
     * Émet la facture de la période qui commence le jour donné.
     */
    public function issueInvoice(Subscription $subscription, CarbonImmutable $periodStart): SubscriptionInvoice
    {
        $subscription->loadMissing('plan');
        $cycle = $subscription->billing_cycle;
        $periodEnd = $periodStart->addMonthsNoOverflow($cycle->months())->subDay();

        $invoice = $subscription->invoices()->create([
            'user_id' => $subscription->user_id,
            'plan_name' => $subscription->plan->name,
            'billing_cycle' => $cycle,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'amount' => $subscription->plan->priceFor($cycle),
            'statut' => InvoiceStatus::Unpaid,
            'due_on' => $periodStart->addDays(self::graceDays()),
        ]);

        $subscription->update([
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'statut' => $invoice->amount > 0 ? SubscriptionStatus::PastDue : SubscriptionStatus::Active,
        ]);

        // Une formule gratuite est réglée d'office
        if ($invoice->amount === 0) {
            $invoice->update(['statut' => InvoiceStatus::Paid, 'paid_at' => now()]);
        } else {
            $subscription->user->notify(new SubscriptionUpdated($subscription, SubscriptionUpdated::INVOICE_ISSUED, $invoice));
        }

        return $invoice;
    }

    /**
     * Paiement reçu hors ligne (Mobile Money, virement, espèces) ou en ligne (CinetPay, sans auteur) : la facture est soldée
     * et l'abonnement redevient actif s'il ne reste rien à payer.
     */
    public function markPaid(SubscriptionInvoice $invoice, PaymentMethod $method, ?string $reference, ?User $recordedBy): void
    {
        if (! $invoice->isUnpaid()) {
            throw new WorkflowException('Cette facture n’est pas à payer.');
        }

        DB::transaction(function () use ($invoice, $method, $reference, $recordedBy): void {
            $invoice->update([
                'statut' => InvoiceStatus::Paid,
                'paid_at' => now(),
                'payment_method' => $method,
                'payment_reference' => $reference,
                'recorded_by' => $recordedBy?->id,
            ]);

            $subscription = $invoice->subscription;

            if (! $subscription->invoices()->where('statut', InvoiceStatus::Unpaid)->exists()
                && in_array($subscription->statut, [SubscriptionStatus::PastDue, SubscriptionStatus::Suspended], true)) {
                $subscription->update(['statut' => SubscriptionStatus::Active, 'suspended_at' => null]);
            }
        });

        $invoice->user->notify(new SubscriptionUpdated($invoice->subscription, SubscriptionUpdated::INVOICE_PAID, $invoice));
    }

    public function cancelInvoice(SubscriptionInvoice $invoice): void
    {
        if (! $invoice->isUnpaid()) {
            throw new WorkflowException('Seule une facture à payer peut être annulée.');
        }

        $invoice->update(['statut' => InvoiceStatus::Cancelled]);

        $subscription = $invoice->subscription;

        if (! $subscription->invoices()->where('statut', InvoiceStatus::Unpaid)->exists()
            && in_array($subscription->statut, [SubscriptionStatus::PastDue, SubscriptionStatus::Suspended], true)) {
            $subscription->update(['statut' => SubscriptionStatus::Active, 'suspended_at' => null]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TRAITEMENT QUOTIDIEN
    |--------------------------------------------------------------------------
    */

    /**
     * Fin d'essai, renouvellements et suspensions (commande planifiée chaque jour).
     *
     * @return array{invoiced: int, reminded: int, suspended: int}
     */
    public function process(?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();
        $invoiced = 0;
        $reminded = 0;
        $suspended = 0;

        // Essais terminés : première facture
        Subscription::query()
            ->whereHas('user', fn ($query) => $query->where('subscription_exempt', false))
            ->where('statut', SubscriptionStatus::Trial)
            ->where('trial_ends_at', '<=', $today->endOfDay())
            ->with(['plan', 'user'])
            ->each(function (Subscription $subscription) use (&$invoiced): void {
                $this->issueInvoice($subscription, CarbonImmutable::parse($subscription->trial_ends_at)->startOfDay());
                $invoiced++;
            });

        // Périodes payées terminées : facture de la période suivante
        Subscription::query()
            ->whereHas('user', fn ($query) => $query->where('subscription_exempt', false))
            ->where('statut', SubscriptionStatus::Active)
            ->whereDate('current_period_end', '<', $today->toDateString())
            ->with(['plan', 'user'])
            ->each(function (Subscription $subscription) use (&$invoiced): void {
                $this->issueInvoice($subscription, CarbonImmutable::parse($subscription->current_period_end)->addDay());
                $invoiced++;
            });

        // Échéance proche : un rappel par facture
        SubscriptionInvoice::query()
            ->whereHas('user', fn ($query) => $query->where('subscription_exempt', false))
            ->where('statut', InvoiceStatus::Unpaid)
            ->whereNull('reminder_sent_at')
            ->whereDate('due_on', '>', $today->toDateString())
            ->whereDate('due_on', '<=', $today->addDays(self::REMINDER_DAYS)->toDateString())
            ->with(['subscription.plan', 'user'])
            ->each(function (SubscriptionInvoice $invoice) use (&$reminded): void {
                $invoice->update(['reminder_sent_at' => now()]);
                $invoice->user?->notify(new SubscriptionUpdated($invoice->subscription, SubscriptionUpdated::INVOICE_REMINDER, $invoice));
                $reminded++;
            });

        // Échéance dépassée : suspension
        Subscription::query()
            ->whereHas('user', fn ($query) => $query->where('subscription_exempt', false))
            ->where('statut', SubscriptionStatus::PastDue)
            ->whereHas('invoices', fn ($query) => $query->where('statut', InvoiceStatus::Unpaid)->whereDate('due_on', '<', $today->toDateString()))
            ->with('user')
            ->each(function (Subscription $subscription) use (&$suspended): void {
                $subscription->update(['statut' => SubscriptionStatus::Suspended, 'suspended_at' => now()]);
                $subscription->user->notify(new SubscriptionUpdated($subscription, SubscriptionUpdated::SUSPENDED));
                Notification::send(User::query()->backOffice()->get(), new AdminSubscriptionAlert($subscription, AdminSubscriptionAlert::SUSPENDED));
                $suspended++;
            });

        return ['invoiced' => $invoiced, 'reminded' => $reminded, 'suspended' => $suspended];
    }

    /*
    |--------------------------------------------------------------------------
    | LIMITES DE LA FORMULE
    |--------------------------------------------------------------------------
    */

    /**
     * Raison pour laquelle le propriétaire ne peut pas ajouter d'établissement (null : il peut).
     */
    public function propertyBlocker(User $owner): ?string
    {
        if (! self::required() || $owner->isSubscriptionExempt()) {
            return null;
        }

        $subscription = $owner->currentSubscription;

        if (! $subscription || ! $subscription->isInGoodStanding()) {
            return 'Un abonnement en règle est nécessaire pour ajouter un établissement.';
        }

        $max = $subscription->plan->max_properties;

        return $max !== null && $owner->properties()->count() >= $max
            ? "Votre formule « {$subscription->plan->name} » permet {$max} établissement".($max > 1 ? 's' : '').'. Passez à une formule supérieure pour en ajouter.'
            : null;
    }

    /**
     * Raison pour laquelle le propriétaire ne peut pas ajouter d'unité (null : il peut).
     */
    public function unitBlocker(User $owner): ?string
    {
        if (! self::required() || $owner->isSubscriptionExempt()) {
            return null;
        }

        $subscription = $owner->currentSubscription;

        if (! $subscription || ! $subscription->isInGoodStanding()) {
            return 'Un abonnement en règle est nécessaire pour ajouter une unité.';
        }

        $max = $subscription->plan->max_units;

        return $max !== null && $this->unitCount($owner) >= $max
            ? "Votre formule « {$subscription->plan->name} » permet {$max} unité".($max > 1 ? 's' : '').'. Passez à une formule supérieure pour en ajouter.'
            : null;
    }

    public function unitCount(User $owner): int
    {
        return Unit::query()->whereHas('property', fn ($query) => $query->ownedBy($owner))->count();
    }

    /**
     * Une formule plus petite ne peut pas être choisie si le propriétaire la dépasse déjà.
     */
    private function assertFits(User $owner, SubscriptionPlan $plan): void
    {
        $properties = $owner->properties()->count();
        $units = $this->unitCount($owner);

        if ($plan->max_properties !== null && $properties > $plan->max_properties) {
            throw new WorkflowException("La formule « {$plan->name} » permet {$plan->max_properties} établissement(s) ; vous en avez {$properties}.");
        }

        if ($plan->max_units !== null && $units > $plan->max_units) {
            throw new WorkflowException("La formule « {$plan->name} » permet {$plan->max_units} unité(s) ; vous en avez {$units}.");
        }
    }
}
