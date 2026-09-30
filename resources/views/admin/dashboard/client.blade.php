{{-- Tableau de bord d'un client : prochain séjour, chiffres clés, historique des réservations --}}
@php
    use App\Enums\ReservationStatus;

    $statusTone = fn (ReservationStatus $status): string => match ($status) {
        ReservationStatus::Confirmed => 'good',
        ReservationStatus::Completed => 'info',
        ReservationStatus::Pending => 'warning',
        ReservationStatus::Cancelled => 'critical',
        default => 'neutral',
    };
@endphp

{{-- ========== Prochain séjour ========== --}}
@if ($nextStay)
    @php
        $property = $nextStay->property;
        $daysLeft = (int) today()->diffInDays($nextStay->check_in, false);
    @endphp

    <section class="stay-hero">
        @if ($property?->coverImage)
            <img src="{{ $property->coverImage->url }}" alt="" class="stay-hero-bg">
        @endif

        <div class="stay-hero-content">
            <span class="stay-hero-kicker">
                <i class="fa-solid fa-plane-arrival"></i>
                @if ($daysLeft > 1)
                    Votre prochain séjour · dans {{ $daysLeft }} jours
                @elseif ($daysLeft === 1)
                    Votre prochain séjour · demain
                @elseif ($daysLeft === 0)
                    Votre séjour commence aujourd’hui
                @else
                    Séjour en cours
                @endif
            </span>
            <h2>{{ $property?->name }}</h2>
            <p><i class="fa-solid fa-location-dot"></i> {{ collect([$property?->district, $property?->city?->name])->filter()->implode(', ') }}</p>

            <dl class="stay-hero-facts">
                <div><dt>Arrivée</dt><dd>{{ Str::ucfirst($nextStay->check_in->translatedFormat('D j M')) }} · dès {{ substr((string) $property?->check_in_from, 0, 5) }}</dd></div>
                <div><dt>Départ</dt><dd>{{ Str::ucfirst($nextStay->check_out->translatedFormat('D j M')) }}</dd></div>
                <div><dt>Durée</dt><dd>{{ $nextStay->nights }} nuit{{ $nextStay->nights > 1 ? 's' : '' }}</dd></div>
                <div><dt>Référence</dt><dd>{{ $nextStay->reference }}</dd></div>
            </dl>
        </div>
    </section>
@else
    <section class="stay-hero stay-hero-empty">
        <div class="stay-hero-content">
            <span class="stay-hero-kicker"><i class="fa-solid fa-suitcase-rolling"></i> Aucun séjour prévu</span>
            <h2>Où partez-vous la prochaine fois ?</h2>
            <p>Résidences meublées, villas et hôtels sélectionnés partout en Côte d’Ivoire.</p>
            <a href="{{ route('residences.index') }}" class="stay-hero-btn">
                Explorer les résidences
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </section>
@endif

{{-- ========== Chiffres clés ========== --}}
<div class="kpi-grid">
    @foreach ($summary as $item)
        <article class="kpi-card">
            <div class="kpi-top">
                <span class="kpi-icon {{ $loop->even ? 'kpi-icon-gold' : '' }}"><i class="fa-solid {{ $item['icon'] }}"></i></span>
                <span class="kpi-label">{{ $item['label'] }}</span>
            </div>
            <p class="kpi-value kpi-value-sm">{{ $item['value'] }}</p>
        </article>
    @endforeach
</div>

{{-- ========== Mes réservations ========== --}}
<section class="dash-card">
    <header class="dash-card-header">
        <div>
            <h2>Mes réservations</h2>
            <p>Vos séjours passés et à venir</p>
        </div>
    </header>

    @if ($reservations->isEmpty())
        <div class="empty-state">
            <i class="fa-regular fa-calendar"></i>
            <strong>Aucune réservation</strong>
            <span>Vos réservations apparaîtront ici.</span>
            <a href="{{ route('residences.index') }}" class="btn-primary">Trouver une résidence</a>
        </div>
    @else
        <div class="table-card dash-table">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Résidence</th>
                        <th>Séjour</th>
                        <th>Montant</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reservations as $reservation)
                        <tr>
                            <td class="cell-main">
                                <span class="cell-entity-text">
                                    <strong>{{ $reservation->property?->name }}</strong>
                                    <small>{{ $reservation->reference }} · {{ $reservation->property?->city?->name }}</small>
                                </span>
                            </td>
                            <td class="text-nowrap">
                                <span class="cell-stack">
                                    <span>{{ $reservation->check_in->format('d/m/y') }} → {{ $reservation->check_out->format('d/m/y') }}</span>
                                    <small>{{ $reservation->nights }} nuit{{ $reservation->nights > 1 ? 's' : '' }}</small>
                                </span>
                            </td>
                            <td class="text-nowrap"><strong>{{ number_format($reservation->total_amount, 0, ',', ' ') }} FCFA</strong></td>
                            <td><span class="status-pill status-{{ $statusTone($reservation->status) }}">{{ $reservation->status->label() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
