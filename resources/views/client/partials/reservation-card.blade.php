{{-- Carte d'une réservation dans l'espace client. Paramètre : $reservation (avec property.coverImage, items.unit) --}}
@php
    $property = $reservation->property;
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $balance = $reservation->balanceDue();
    $isActive = in_array($reservation->statut, [App\Enums\ReservationStatus::Pending, App\Enums\ReservationStatus::Confirmed], true);
@endphp

<article class="acc-resa-card">
    <a href="{{ route('client.reservations.show', $reservation) }}" class="acc-resa-media" tabindex="-1" aria-hidden="true">
        <img src="{{ $property?->coverImage?->url ?? asset('assets/images/home/residence-1.webp') }}" alt="" loading="lazy">
    </a>

    <div class="acc-resa-body">
        <div class="acc-resa-top">
            <span class="acc-pill tone-{{ $reservation->statut->tone() }}">{{ $reservation->statut->label() }}</span>
            <small>{{ $reservation->reference }}</small>
        </div>

        <h3><a href="{{ route('client.reservations.show', $reservation) }}">{{ $property?->name ?? 'Établissement' }}</a></h3>

        <p class="acc-resa-meta">
            <span><i class="fa-regular fa-calendar"></i> {{ $reservation->check_in->translatedFormat('d M') }} → {{ $reservation->check_out->translatedFormat('d M Y') }}</span>
            <span><i class="fa-solid fa-moon"></i> {{ $reservation->nights }} nuit{{ $reservation->nights > 1 ? 's' : '' }}</span>
            @if ($property?->city)
                <span><i class="fa-solid fa-location-dot"></i> {{ $property->city->name }}</span>
            @endif
        </p>

        <div class="acc-resa-foot">
            <div>
                <strong>{{ $money($reservation->total_amount) }}</strong>
                @if ($isActive && $balance > 0)
                    <small class="is-due">Reste {{ $money($balance) }} à régler</small>
                @elseif ($reservation->amount_paid > 0)
                    <small class="is-paid"><i class="fa-solid fa-circle-check"></i> Réglé</small>
                @endif
            </div>
            <a href="{{ route('client.reservations.show', $reservation) }}" class="acc-btn acc-btn-light">
                Détails <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</article>
