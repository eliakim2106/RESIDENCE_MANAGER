{{-- Résidences en ligne : coups de cœur puis mieux notées (HomeController) --}}
@if ($residences->isNotEmpty())
    @php
        $favorites = auth()->user()?->isClient() ? auth()->user()->favorites()->pluck('properties.id')->all() : [];
        $heading = $site->content('home_featured');
    @endphp

    <section class="home-section" id="residences">
        <div class="container">

            <div class="home-section-head home-section-head-split" data-reveal>
                <div>
                    @if ($heading['kicker'])
                        <span class="home-kicker">{{ $heading['kicker'] }}</span>
                    @endif
                    <h2 class="home-title">{{ $heading['title'] }} @if ($heading['highlight'])<span>{{ $heading['highlight'] }}</span>@endif</h2>
                </div>

                <a href="{{ route('residences.index') }}" class="home-link-arrow">
                    Toutes les résidences
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="home-residences-grid">
                @foreach ($residences as $index => $residence)
                    @php
                        $url = route('residences.show', $residence);
                        $badge = $residence->is_featured ? 'Coup de cœur' : ($residence->published_at?->gt(now()->subDays(30)) ? 'Nouveau' : null);
                        $location = collect([$residence->neighborhood ?: $residence->district, $residence->city?->name])->filter()->unique()->implode(', ');
                        $isFavorite = in_array($residence->id, $favorites, true);
                    @endphp
                    <article class="home-residence" data-reveal style="--reveal-delay: {{ ($index % 3) * 120 }}ms">

                        <a href="{{ $url }}" class="home-residence-media" aria-label="Voir {{ $residence->name }}">
                            <img src="{{ $residence->coverImage?->url ?? asset('assets/images/home/residence-'.(($index % 3) + 1).'.webp') }}" alt="{{ $residence->name }}" loading="lazy">
                            @if ($badge)
                                <span class="home-residence-badge">{{ $badge }}</span>
                            @endif
                        </a>

                        @if (auth()->user()?->isClient())
                            <form method="POST" action="{{ route('client.favorites.toggle', $residence) }}" class="home-residence-fav-form">
                                @csrf
                                <button type="submit" class="home-residence-fav {{ $isFavorite ? 'is-active' : '' }}" aria-label="{{ $isFavorite ? 'Retirer' : 'Ajouter' }} {{ $residence->name }} {{ $isFavorite ? 'des' : 'aux' }} favoris" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}">
                                    <i class="fa-{{ $isFavorite ? 'solid' : 'regular' }} fa-heart"></i>
                                </button>
                            </form>
                        @endif

                        <div class="home-residence-body">
                            <div class="home-residence-meta">
                                <span><i class="fa-solid fa-location-dot"></i> {{ $location }}</span>
                                @if ($residence->reviews_count > 0)
                                    <span class="home-residence-rating">
                                        <i class="fa-solid fa-star"></i>
                                        {{ number_format((float) $residence->rating_average, 1, ',', ' ') }}
                                        <small>({{ $residence->reviews_count }})</small>
                                    </span>
                                @endif
                            </div>

                            <h3>
                                <a href="{{ $url }}">{{ $residence->name }}</a>
                            </h3>

                            <p class="home-residence-type">
                                {{ $residence->propertyType?->name }}@if ($residence->capacite_max) · jusqu’à {{ $residence->capacite_max }} adultes @endif
                            </p>

                            @if ($residence->equipments->isNotEmpty())
                                <ul class="home-residence-features">
                                    @foreach ($residence->equipments->take(3) as $equipment)
                                        <li><i class="fa-solid {{ $equipment->fa_icon }}"></i> {{ $equipment->name }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="home-residence-footer">
                                <p class="home-residence-price">
                                    @if ($residence->prix_min)
                                        <span>Dès</span>
                                        <strong>{{ number_format((int) $residence->prix_min, 0, ',', ' ') }} FCFA</strong>
                                        <span>/ nuit</span>
                                    @endif
                                </p>

                                <a href="{{ $url }}" class="home-residence-cta" aria-label="Réserver {{ $residence->name }}">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>

                    </article>
                @endforeach
            </div>

        </div>
    </section>
@endif
