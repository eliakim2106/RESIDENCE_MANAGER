<?php

namespace App\Services;

use App\Enums\PayoutMethod;
use App\Enums\PayoutStatus;
use App\Enums\ReservationStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\WorkflowException;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use App\Notifications\PayoutRecorded;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reversements aux propriétaires.
 *
 * DS Holding encaisse les paiements en ligne ; chaque paiement doit au propriétaire son montant net
 * (encaissé − remboursé) moins la commission de sa formule. Ce qui reste à reverser se calcule paiement
 * par paiement : dû − déjà reversé. Un remboursement fait après un reversement donne une ligne négative,
 * déduite au reversement suivant. Les paiements encaissés sur place par l'établissement ne sont pas concernés.
 *
 * Une ligne est « disponible » une fois le séjour commencé (ou la réservation close) ; avant, elle est « à venir ».
 */
class PayoutLedger
{
    /**
     * Paiements encaissés par DS Holding, avec la part déjà reversée (reversements non annulés).
     *
     * @return Builder<Payment>
     */
    public function payments(?User $owner = null): Builder
    {
        return Payment::query()
            ->where('provider', '!=', 'manuel')
            ->whereIn('statut', [TransactionStatus::Accepted, TransactionStatus::Refunded])
            ->whereHas('reservation.property', fn (Builder $query) => $owner ? $query->ownedBy($owner) : $query)
            ->withSum(['payoutItems as paid_out' => fn (Builder $query) => $query->whereHas('payout', fn (Builder $query) => $query->where('statut', PayoutStatus::Paid))], 'gross_amount')
            ->with('reservation.property');
    }

    /**
     * Commission prélevée sur les réservations du propriétaire, selon la formule de son abonnement (en %).
     */
    public function commissionRate(User $owner): float
    {
        return (float) ($owner->currentSubscription?->plan?->commission_rate ?? 0);
    }

    /**
     * Ce qui reste à reverser au propriétaire, paiement par paiement (lignes à zéro écartées).
     *
     * @return Collection<int, array{payment: Payment, gross: int, rate: float, commission: int, amount: int, available: bool}>
     */
    public function lines(User $owner, ?CarbonImmutable $today = null): Collection
    {
        return $this->linesFor($this->payments($owner)->oldest('paid_at')->get(), $this->commissionRate($owner), $today);
    }

    /**
     * Solde du propriétaire : disponible (à reverser maintenant) et à venir (séjours pas encore commencés).
     *
     * @return array{available: int, upcoming: int, gross: int, commission: int, lines: Collection<int, array{payment: Payment, gross: int, rate: float, commission: int, amount: int, available: bool}>}
     */
    public function balance(User $owner, ?CarbonImmutable $today = null): array
    {
        $lines = $this->lines($owner, $today);
        $available = $lines->where('available', true);

        return [
            'available' => (int) $available->sum('amount'),
            'upcoming' => (int) $lines->where('available', false)->sum('amount'),
            'gross' => (int) $available->sum('gross'),
            'commission' => (int) $available->sum('commission'),
            'lines' => $lines,
        ];
    }

    /**
     * Soldes de plusieurs propriétaires en une seule requête de paiements.
     *
     * @param  Collection<int, User>  $owners  propriétaires, avec currentSubscription.plan chargé
     * @return array<int, array{available: int, upcoming: int, count: int}>
     */
    public function balances(Collection $owners, ?CarbonImmutable $today = null): array
    {
        $byOwner = $this->payments()
            ->whereHas('reservation.property', fn (Builder $query) => $query->whereIn('owner_id', $owners->modelKeys()))
            ->get()
            ->groupBy(fn (Payment $payment) => $payment->reservation->property->owner_id);

        return $owners->mapWithKeys(function (User $owner) use ($byOwner, $today): array {
            $lines = $this->linesFor($byOwner->get($owner->id, collect()), $this->commissionRate($owner), $today);

            return [$owner->id => [
                'available' => (int) $lines->where('available', true)->sum('amount'),
                'upcoming' => (int) $lines->where('available', false)->sum('amount'),
                'count' => $lines->where('available', true)->count(),
            ]];
        })->all();
    }

    /*
    |--------------------------------------------------------------------------
    | REVERSEMENTS
    |--------------------------------------------------------------------------
    */

    /**
     * Enregistre le virement du solde disponible au propriétaire.
     */
    public function record(User $owner, PayoutMethod $method, ?string $reference, ?string $notes, User $recordedBy, ?CarbonImmutable $paidAt = null): Payout
    {
        $lines = $this->lines($owner)->where('available', true);
        $total = (int) $lines->sum('amount');

        if ($lines->isEmpty() || $total <= 0) {
            throw new WorkflowException('Rien à reverser à '.$owner->name.' pour le moment.');
        }

        $payout = DB::transaction(function () use ($owner, $method, $reference, $notes, $recordedBy, $paidAt, $lines, $total): Payout {
            $payout = Payout::create([
                'user_id' => $owner->id,
                'gross_amount' => max(0, (int) $lines->sum('gross')),
                'commission_amount' => max(0, (int) $lines->sum('commission')),
                'amount' => $total,
                'method' => $method,
                'reference' => $reference,
                'account' => $owner->payoutAccountSummary(),
                'notes' => $notes,
                'statut' => PayoutStatus::Paid,
                'paid_at' => $paidAt ?? now(),
                'recorded_by' => $recordedBy->id,
            ]);

            $payout->items()->createMany($lines->map(fn (array $line): array => [
                'payment_id' => $line['payment']->id,
                'gross_amount' => $line['gross'],
                'commission_rate' => $line['rate'],
                'commission_amount' => $line['commission'],
                'amount' => $line['amount'],
            ])->values()->all());

            return $payout;
        });

        $owner->notify(new PayoutRecorded($payout));

        return $payout;
    }

    /**
     * Annule un reversement saisi par erreur : les montants redeviennent à reverser.
     */
    public function cancel(Payout $payout): void
    {
        if (! $payout->isPaid()) {
            throw new WorkflowException('Ce reversement est déjà annulé.');
        }

        $payout->update(['statut' => PayoutStatus::Cancelled, 'cancelled_at' => now()]);
    }

    /*
    |--------------------------------------------------------------------------
    | CALCUL
    |--------------------------------------------------------------------------
    */

    /**
     * @param  Collection<int, Payment>  $payments  chargés avec paid_out et reservation
     * @return Collection<int, array{payment: Payment, gross: int, rate: float, commission: int, amount: int, available: bool}>
     */
    private function linesFor(Collection $payments, float $rate, ?CarbonImmutable $today): Collection
    {
        $today ??= CarbonImmutable::today();

        return $payments
            ->map(function (Payment $payment) use ($rate, $today): array {
                $gross = $payment->netAmount() - (int) $payment->paid_out;
                $commission = (int) round($gross * $rate / 100);

                return [
                    'payment' => $payment,
                    'gross' => $gross,
                    'rate' => $rate,
                    'commission' => $commission,
                    'amount' => $gross - $commission,
                    'available' => $gross < 0 || $this->stayStarted($payment, $today),
                ];
            })
            ->filter(fn (array $line): bool => $line['gross'] !== 0)
            ->values();
    }

    /**
     * Séjour commencé, ou réservation close (annulée, terminée, non présentée) : plus rien ne peut changer.
     */
    private function stayStarted(Payment $payment, CarbonImmutable $today): bool
    {
        $reservation = $payment->reservation;

        return ! in_array($reservation->statut, [ReservationStatus::Pending, ReservationStatus::Confirmed], true)
            || $reservation->check_in->lte($today);
    }
}
