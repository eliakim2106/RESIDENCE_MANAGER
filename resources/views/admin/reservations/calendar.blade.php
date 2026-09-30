@extends('layouts.admin')

@section('title', 'Calendrier d’occupation')

@php
    $previous = $month->subMonth()->format('Y-m');
    $next = $month->addMonth()->format('Y-m');
    $monthLabel = Str::ucfirst($month->translatedFormat('F Y'));
    $link = fn (array $query): string => route('admin.reservations.calendar', array_filter([
        'etablissement' => $property?->slug,
        ...$query,
    ]));

    // Info-bulle d'une case : unité, date, occupation et séjours
    $tooltip = function (array $cell, string $date, $unit): string {
        $lines = [$unit->name.' · '.Carbon\Carbon::parse($date)->translatedFormat('l d F')];

        $lines[] = match ($cell['state']) {
            'closed' => 'Fermé à la réservation',
            'blocked' => 'Indisponible (maintenance)',
            default => $cell['capacity'] > 1
                ? $cell['booked'].' / '.$cell['capacity'].' occupé'.($cell['booked'] > 1 ? 's' : '')
                : ($cell['booked'] > 0 ? 'Occupé' : 'Libre'),
        };

        foreach ($cell['reservations'] as $reservation) {
            $lines[] = $reservation->reference.' · '.$reservation->guest_name;
        }

        return implode("\n", $lines);
    };
@endphp

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-regular fa-calendar"></i></span>
            <div>
                <h1>Calendrier d’occupation</h1>
                <p>Occupation des unités jour par jour{{ $property ? ' · '.$property->name : '' }}.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <a href="{{ route('admin.reservations.index', array_filter(['etablissement' => $property?->slug])) }}" class="btn-secondary">
                <i class="fa-solid fa-list"></i>
                Liste des réservations
            </a>
            @if ($properties->count() > 1)
                <form method="GET" class="list-filter" aria-label="Choisir l’établissement">
                    <input type="hidden" name="mois" value="{{ $month->format('Y-m') }}">
                    <i class="fa-solid fa-building"></i>
                    <select name="etablissement" data-auto-submit aria-label="Établissement">
                        @foreach ($properties as $option)
                            <option value="{{ $option->slug }}" @selected($property?->is($option))>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    </div>

    @if (! $property)
        <div class="table-card">
            <div class="empty-state">
                <i class="fa-regular fa-calendar"></i>
                <strong>Aucun établissement</strong>
                <span>Le calendrier apparaîtra dès qu’un établissement aura des unités.</span>
            </div>
        </div>
    @else
        <section class="occ-card">
            <div class="occ-toolbar">
                <div class="occ-nav">
                    <a href="{{ $link(['mois' => $previous]) }}" class="occ-nav-btn" aria-label="Mois précédent"><i class="fa-solid fa-chevron-left"></i></a>
                    <h2>{{ $monthLabel }}</h2>
                    <a href="{{ $link(['mois' => $next]) }}" class="occ-nav-btn" aria-label="Mois suivant"><i class="fa-solid fa-chevron-right"></i></a>
                    @unless ($month->isSameMonth(now()))
                        <a href="{{ $link([]) }}" class="btn-secondary btn-sm">Aujourd’hui</a>
                    @endunless
                </div>

                <div class="occ-rate">
                    <span class="occ-rate-value">{{ number_format($occupancy, $occupancy < 10 && $occupancy != floor($occupancy) ? 1 : 0, ',', ' ') }} %</span>
                    <span class="occ-rate-label">taux d’occupation<br>du mois</span>
                </div>

                <ul class="occ-legend" aria-label="Légende">
                    <li><span class="occ-swatch state-free"></span> Libre</li>
                    <li><span class="occ-swatch state-partial"></span> Partiellement occupé</li>
                    <li><span class="occ-swatch state-full"></span> Complet</li>
                    <li><span class="occ-swatch state-blocked"></span> Maintenance / fermé</li>
                </ul>
            </div>

            @if ($rows->isEmpty())
                <div class="empty-state">
                    <i class="fa-solid fa-door-open"></i>
                    <strong>Aucune unité active</strong>
                    <span>Ajoutez ou activez des unités pour suivre leur occupation.</span>
                </div>
            @else
                <div class="occ-scroll" tabindex="0" aria-label="Grille d’occupation, défilable horizontalement">
                    <table class="occ-grid">
                        <thead>
                            <tr>
                                <th scope="col" class="occ-unit-head">Unité</th>
                                @foreach ($calendar->days as $day)
                                    <th scope="col" class="{{ $day->isWeekend() ? 'is-weekend' : '' }} {{ $day->isToday() ? 'is-today' : '' }}">
                                        <span>{{ Str::ucfirst(mb_substr($day->translatedFormat('D'), 0, 2)) }}</span>
                                        <strong>{{ $day->format('d') }}</strong>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <th scope="row" class="occ-unit">
                                        <strong>{{ $row['unit']->name }}</strong>
                                        <small>{{ $row['unit']->unitType?->name }}{{ $row['unit']->quantity > 1 ? ' · '.$row['unit']->quantity.' exemplaires' : '' }}</small>
                                    </th>
                                    @foreach ($row['cells'] as $date => $cell)
                                        @php
                                            $day = Carbon\Carbon::parse($date);
                                            $single = count($cell['reservations']) === 1 ? $cell['reservations'][0] : null;
                                            $start = $cell['starts'][0] ?? null;
                                        @endphp
                                        <td class="occ-cell state-{{ $cell['state'] }} {{ $day->isWeekend() ? 'is-weekend' : '' }} {{ $day->isToday() ? 'is-today' : '' }}"
                                            title="{{ $tooltip($cell, $date, $row['unit']) }}">
                                            @if ($single)
                                                <a href="{{ route('admin.reservations.show', $single) }}" class="occ-link" aria-label="{{ $single->reference }}">
                                            @endif
                                            @if ($start)
                                                <span class="occ-start">{{ collect(preg_split('/[\s-]+/', trim($start->guest_name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}</span>
                                            @elseif ($cell['capacity'] > 1 && $cell['booked'] > 0)
                                                <span class="occ-count">{{ $cell['booked'] }}/{{ $cell['capacity'] }}</span>
                                            @endif
                                            @if ($single)
                                                </a>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="occ-hint">
                    <i class="fa-solid fa-circle-info"></i>
                    Les initiales marquent le jour d’arrivée d’un client. Survolez une case pour voir le détail ; cliquez pour ouvrir la réservation.
                </p>
            @endif
        </section>
    @endif
@endsection
