@php
    // Contenu réglé dans Paramètres du site > Contenu des pages > Accueil
    $content = $site->content('home_steps');
    $steps = $content['items'];
@endphp

<section class="home-section home-steps">
    <div class="container">

        <div class="home-section-head" data-reveal>
            @if ($content['kicker'])
                <span class="home-kicker">{{ $content['kicker'] }}</span>
            @endif
            <h2 class="home-title">{{ $content['title'] }} @if ($content['highlight'])<span>{{ $content['highlight'] }}</span>@endif</h2>
        </div>

        <ol class="home-steps-list" style="--steps: {{ count($steps) }}">
            @foreach ($steps as $index => $step)
                <li class="home-step" data-reveal style="--reveal-delay: {{ $index * 140 }}ms">
                    <span class="home-step-number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="home-step-icon"><i class="fa-solid {{ $step['icon'] }}"></i></span>
                    <h3>{{ $step['title'] }}</h3>
                    @if ($step['text'])
                        <p>{{ $step['text'] }}</p>
                    @endif
                </li>
            @endforeach
        </ol>

    </div>
</section>
