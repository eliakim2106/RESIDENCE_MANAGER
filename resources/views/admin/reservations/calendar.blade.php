@extends('layouts.admin')

@section('title', 'Calendrier d’occupation')

@php
    use App\Enums\ReservationStatus;

    $previous = $month->subMonth()->format('Y-m');
    $next = $month->addMonth()->format('Y-m');
    $monthLabel = Str::ucfirst($month->translatedFormat('F Y'));
    $link = fn (array $query): string => route('admin.reservations.calendar', array_filter([
        'etablissement' => $property?->slug,
        'vue' => $view === 'planning' ? 'planning' : null,
        ...$query,
    ]));
    $percent = fn (float $value): string => number_format($value, $value < 10 && $value != floor($value) ? 1 : 0, ',', ' ').' %';

    // Lignes de barres visibles par semaine ; au-delà, « + N » sur le jour concerné
    $maxLanes = 3;

    // Ton d'une barre : en séjour aujourd'hui, confirmé, en attente, terminé
    $tone = function ($reservation): string {
        $today = now()->startOfDay();

        return match (true) {
            $reservation->statut === ReservationStatus::Pending => 'pending',
            $reservation->statut === ReservationStatus::Completed => 'completed',
            $reservation->check_in->lte($today) && $reservation->check_out->gt($today) => 'inhouse',
            default => 'confirmed',
        };
    };

    // Logements d'un séjour : « 2 × Chambre Deluxe », ou « Chambre Deluxe + 1 »
    $units = function ($reservation): string {
        $items = $reservation->items;
        $first = $items->first();

        if (! $first) {
            return '';
        }

        $label = ($first->quantity > 1 ? $first->quantity.' × ' : '').($first->unit?->name ?? 'Logement');

        return $items->count() > 1 ? $label.' + '.($items->count() - 1) : $label;
    };

    $nights = fn ($reservation): int => (int) $reservation->check_in->diffInDays($reservation->check_out);

    $tooltip = fn ($reservation): string => implode("\n", array_filter([
        $reservation->reference.' · '.$reservation->guest_name,
        'Du '.$reservation->check_in->translatedFormat('d M').' au '.$reservation->check_out->translatedFormat('d M Y').' · '.$nights($reservation).' nuit'.($nights($reservation) > 1 ? 's' : ''),
        $reservation->items->map(fn ($item) => $item->quantity.' × '.($item->unit?->name ?? 'Logement'))->implode(', '),
        $reservation->statut->label(),
    ]));

    // Info-bulle d'une case du planning : unité, date, occupation et séjours
    $cellTooltip = function (array $cell, string $date, $unit): string {
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
                <p>Séjours et occupation jour par jour{{ $property ? ' · '.$property->name : '' }}.</p>
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
                    @if ($view === 'planning')
                        <input type="hidden" name="vue" value="planning">
                    @endif
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
        <section class="cal-shell">

            {{-- ========== Barre d'outils ========== --}}
            <header class="cal-toolbar">
                <div class="cal-nav">
                    <a href="{{ $link(['mois' => $previous]) }}" class="cal-nav-btn" aria-label="Mois précédent"><i class="fa-solid fa-chevron-left"></i></a>
                    <a href="{{ $link(['mois' => $next]) }}" class="cal-nav-btn" aria-label="Mois suivant"><i class="fa-solid fa-chevron-right"></i></a>
                    <h2>{{ $monthLabel }}</h2>
                    @unless ($month->isSameMonth(now()))
                        <a href="{{ $link([]) }}" class="cal-today-btn">Aujourd’hui</a>
                    @endunless
                </div>

                <nav class="cal-views" aria-label="Affichage">
                    <a href="{{ route('admin.reservations.calendar', array_filter(['etablissement' => $property->slug, 'mois' => $month->format('Y-m')])) }}"
                        class="{{ $view === 'mois' ? 'is-active' : '' }}" @if ($view === 'mois') aria-current="page" @endif>
                        <i class="fa-regular fa-calendar"></i> Mois
                    </a>
                    <a href="{{ route('admin.reservations.calendar', ['etablissement' => $property->slug, 'mois' => $month->format('Y-m'), 'vue' => 'planning']) }}"
                        class="{{ $view === 'planning' ? 'is-active' : '' }}" @if ($view === 'planning') aria-current="page" @endif>
                        <i class="fa-solid fa-table-cells"></i> Planning
                    </a>
                </nav>

                <dl class="cal-kpis">
                    <div class="is-rate">
                        <dt>Occupation</dt>
                        <dd>{{ $percent($occupancy) }}</dd>
                    </div>
                    <div>
                        <dt>Séjours</dt>
                        <dd>{{ $summary['stays'] }}</dd>
                    </div>
                    <div>
                        <dt>Arrivées</dt>
                        <dd>{{ $summary['arrivals'] }}</dd>
                    </div>
                    <div>
                        <dt>Départs</dt>
                        <dd>{{ $summary['departures'] }}</dd>
                    </div>
                </dl>
            </header>

            @if ($rows->isEmpty())
                <div class="empty-state">
                    <i class="fa-solid fa-door-open"></i>
                    <strong>Aucune unité active</strong>
                    <span>Ajoutez ou activez des unités pour suivre leur occupation.</span>
                </div>
            @elseif ($view === 'mois')

                <ul class="cal-legend" aria-label="Légende">
                    <li><span class="cal-dot tone-inhouse"></span> En séjour</li>
                    <li><span class="cal-dot tone-confirmed"></span> Confirmée</li>
                    <li><span class="cal-dot tone-pending"></span> En attente</li>
                    <li><span class="cal-dot tone-completed"></span> Terminée</li>
                    <li class="cal-legend-note"><i class="fa-solid fa-arrow-right-to-bracket"></i> arrivées · <i class="fa-solid fa-arrow-right-from-bracket"></i> départs</li>
                </ul>

                {{-- ========== Vue mois ========== --}}
                <div class="cal-month" style="--weeks: {{ count($weeks) }}">
                    <div class="cal-weekdays" aria-hidden="true">
                        @foreach (['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'] as $weekday)
                            <span><b>{{ $weekday }}</b><abbr>{{ mb_substr($weekday, 0, 3) }}</abbr></span>
                        @endforeach
                    </div>

                    @foreach ($weeks as $week)
                        @php
                            $lanes = min($week['lanes'], $maxLanes);
                            // Séjours masqués (au-delà des lignes visibles), par jour de la semaine
                            $hidden = array_fill(0, 7, []);
                            foreach ($week['bars'] as $bar) {
                                if ($bar['lane'] >= $maxLanes) {
                                    for ($i = $bar['start']; $i < $bar['start'] + $bar['span']; $i++) {
                                        $hidden[$i][] = $bar['reservation'];
                                    }
                                }
                            }
                        @endphp
                        <div class="cal-week" style="--lanes: {{ $lanes }}">
                            @foreach ($week['days'] as $i => $day)
                                @php
                                    $rate = $day['available'] > 0 ? $day['booked'] / $day['available'] * 100 : null;
                                    $level = match (true) {
                                        $rate === null || $day['booked'] === 0 => '',
                                        $rate >= 100 => 'is-full',
                                        $rate >= 60 => 'is-high',
                                        default => 'is-some',
                                    };
                                @endphp
                                <div class="cal-day {{ $day['inMonth'] ? '' : 'is-outside' }} {{ $day['date']->isToday() ? 'is-today' : '' }} {{ $day['date']->isWeekend() ? 'is-weekend' : '' }}"
                                    style="grid-column: {{ $i + 1 }}">
                                    <div class="cal-day-head">
                                        <span class="cal-day-number">{{ $day['date']->day }}</span>
                                        @if ($day['inMonth'] && $level)
                                            <span class="cal-day-rate {{ $level }}" title="{{ $day['booked'] }} / {{ $day['available'] }} logements occupés">{{ $percent($rate) }}</span>
                                        @endif
                                    </div>

                                    <div class="cal-day-foot">
                                        @if ($hidden[$i])
                                            <details class="cal-more">
                                                <summary>+ {{ count($hidden[$i]) }} séjour{{ count($hidden[$i]) > 1 ? 's' : '' }}</summary>
                                                <div class="cal-more-pop">
                                                    <strong>{{ Str::ucfirst($day['date']->translatedFormat('l d F')) }}</strong>
                                                    @foreach ($hidden[$i] as $reservation)
                                                        <a href="{{ route('admin.reservations.show', $reservation) }}" class="tone-{{ $tone($reservation) }}">
                                                            <span class="cal-dot tone-{{ $tone($reservation) }}"></span>
                                                            {{ $reservation->guest_name }}
                                                            <small>{{ $units($reservation) }}</small>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif
                                        @if ($day['arrivals'] || $day['departures'])
                                            <span class="cal-moves">
                                                @if ($day['arrivals'])
                                                    <span title="{{ $day['arrivals'] }} arrivée{{ $day['arrivals'] > 1 ? 's' : '' }}"><i class="fa-solid fa-arrow-right-to-bracket"></i> {{ $day['arrivals'] }}</span>
                                                @endif
                                                @if ($day['departures'])
                                                    <span title="{{ $day['departures'] }} départ{{ $day['departures'] > 1 ? 's' : '' }}"><i class="fa-solid fa-arrow-right-from-bracket"></i> {{ $day['departures'] }}</span>
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            @foreach ($week['bars'] as $bar)
                                @continue($bar['lane'] >= $maxLanes)
                                @php $reservation = $bar['reservation']; @endphp
                                <a href="{{ route('admin.reservations.show', $reservation) }}"
                                    class="cal-bar tone-{{ $tone($reservation) }} {{ $bar['continuesBefore'] ? 'continues-before' : '' }} {{ $bar['continuesAfter'] ? 'continues-after' : '' }}"
                                    style="grid-column: {{ $bar['start'] + 1 }} / span {{ $bar['span'] }}; grid-row: {{ $bar['lane'] + 2 }}"
                                    title="{{ $tooltip($reservation) }}">
                                    <strong>{{ $reservation->guest_name }}</strong>
                                    <span>{{ $units($reservation) }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                {{-- ========== Agenda (petits écrans) ========== --}}
                <div class="cal-agenda">
                    <h3>Séjours du mois</h3>
                    @forelse ($stays as $reservation)
                        <a href="{{ route('admin.reservations.show', $reservation) }}" class="cal-agenda-item tone-{{ $tone($reservation) }}">
                            <span class="cal-agenda-date">
                                <b>{{ $reservation->check_in->format('d') }}</b>
                                <small>{{ $reservation->check_in->translatedFormat('M') }}</small>
                            </span>
                            <span class="cal-agenda-body">
                                <strong>{{ $reservation->guest_name }}</strong>
                                <small>{{ $nights($reservation) }} nuit{{ $nights($reservation) > 1 ? 's' : '' }} · départ le {{ $reservation->check_out->translatedFormat('d M') }} · {{ $units($reservation) }}</small>
                            </span>
                            <span class="cal-agenda-status">{{ $reservation->statut->label() }}</span>
                        </a>
                    @empty
                        <p class="cal-agenda-empty">Aucun séjour ce mois-ci.</p>
                    @endforelse
                </div>

            @else

                {{-- ========== Vue planning : unités × jours ========== --}}
                <ul class="cal-legend" aria-label="Légende">
                    <li><span class="occ-swatch state-free"></span> Libre</li>
                    <li><span class="occ-swatch state-partial"></span> Partiellement occupé</li>
                    <li><span class="occ-swatch state-full"></span> Complet</li>
                    <li><span class="occ-swatch state-blocked"></span> Maintenance / fermé</li>
                </ul>

                <div class="occ-scroll" tabindex="0" aria-label="Grille d’occupation, défilable horizontalement">
                    <table class="occ-grid" style="--rows: {{ $rows->count() }}">
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
                                            title="{{ $cellTooltip($cell, $date, $row['unit']) }}">
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
