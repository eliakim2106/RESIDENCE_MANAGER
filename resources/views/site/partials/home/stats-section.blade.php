@php
    $stats = [
        ['fa-building', 15, '+', 'Résidences premium'],
        ['fa-users', 5000, '+', 'Clients satisfaits'],
        ['fa-star', 4.8, '/5', 'Note moyenne'],
        ['fa-headset', 24, 'h/24', 'Assistance dédiée'],
    ];
@endphp

<section class="home-stats" id="chiffres" aria-label="DS HOLDING en chiffres">
    <div class="container">
        <div class="home-stats-grid">
            @foreach ($stats as $index => [$icon, $value, $suffix, $label])
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
