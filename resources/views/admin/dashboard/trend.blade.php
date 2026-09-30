{{-- Évolution d'un indicateur par rapport au mois précédent. Paramètres : $trend (en %, ou null), $previous (nom du mois) --}}
@if ($trend === null)
    <p class="kpi-note">Pas de comparaison avec {{ Str::lower($previous) }}</p>
@else
    <p class="kpi-trend {{ $trend >= 0 ? 'is-up' : 'is-down' }}">
        <i class="fa-solid {{ $trend >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i>
        {{ $trend >= 0 ? '+' : '' }}{{ number_format($trend, $trend >= 100 || $trend <= -100 ? 0 : 1, ',', ' ') }} %
        <span>par rapport à {{ Str::lower($previous) }}</span>
    </p>
@endif
