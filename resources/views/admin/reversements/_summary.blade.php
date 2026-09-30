{{-- Solde d'un propriétaire. Paramètres : $balance (PayoutLedger::balance), $payouts (reversements), $rate (commission en %) --}}
@php
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $paidTotal = (int) $payouts->filter->isPaid()->sum('amount');
@endphp

<div class="resa-today">
    <div class="resa-today-card tone-good {{ $balance['available'] > 0 ? 'has-alert' : '' }}">
        <span class="resa-today-icon"><i class="fa-solid fa-hand-holding-dollar"></i></span>
        <span class="resa-today-text">
            <strong class="is-amount">{{ $money($balance['available']) }}</strong>
            <span>Disponible à reverser</span>
        </span>
    </div>
    <div class="resa-today-card tone-info">
        <span class="resa-today-icon"><i class="fa-solid fa-hourglass-half"></i></span>
        <span class="resa-today-text">
            <strong class="is-amount">{{ $money($balance['upcoming']) }}</strong>
            <span>À venir (séjours pas encore commencés)</span>
        </span>
    </div>
    <div class="resa-today-card tone-gold">
        <span class="resa-today-icon"><i class="fa-solid fa-percent"></i></span>
        <span class="resa-today-text">
            <strong class="is-amount">{{ $money($balance['commission']) }}</strong>
            <span>Commission DS Holding ({{ rtrim(rtrim(number_format($rate, 2, ',', ' '), '0'), ',') }} %)</span>
        </span>
    </div>
    <div class="resa-today-card tone-neutral">
        <span class="resa-today-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
        <span class="resa-today-text">
            <strong class="is-amount">{{ $money($paidTotal) }}</strong>
            <span>Déjà reversé</span>
        </span>
    </div>
</div>
