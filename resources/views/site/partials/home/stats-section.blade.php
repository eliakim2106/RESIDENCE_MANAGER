@php
    // Chiffres réels de la plateforme (HomeController::stats) ; la note n'apparaît qu'avec des avis publiés
    $items = array_values(array_filter([
        ['fa-building', $stats['residences'], '', $stats['residences'] > 1 ? 'Résidences en ligne' : 'Résidence en ligne'],
        ['fa-map-location-dot', $stats['cities'], '', $stats['cities'] > 1 ? 'Villes couvertes' : 'Ville couverte'],
        ['fa-door-open', $stats['units'], '', $stats['units'] > 1 ? 'Logements à réserver' : 'Logement à réserver'],
        $stats['rating'] !== null ? ['fa-star', (float) $stats['rating'], '/10', 'Note moyenne des voyageurs'] : null,
    ]));
@endphp

<section class="home-stats" id="chiffres" aria-label="DS HOLDING en chiffres">
    <div class="container">
        <div class="home-stats-grid">
            @foreach ($items as $index => [$icon, $value, $suffix, $label])
                <div class="home-stat" data-reveal style="--reveal-delay: {{ $index * 90 }}ms">
                    <span class="home-stat-icon"><i class="fa-solid {{ $icon }}"></i></span>
                    <div>
                        <strong>
                            <span data-count="{{ $value }}" data-decimals="{{ is_float($value) ? 1 : 0 }}">{{ is_float($value) ? number_format($value, 1, ',', ' ') : number_format($value, 0, ',', ' ') }}</span>{{ $suffix }}
                        </strong>
                        <span>{{ $label }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
