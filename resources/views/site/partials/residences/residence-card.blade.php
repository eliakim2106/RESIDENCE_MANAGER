{{-- Carte d'un établissement dans la liste. Paramètres : $residence (avec prix_min, capacite_max, en_promo), $nights, $index --}}
@php
    $rating = (float) $residence->rating_average;
    $ratingLabel = match (true) {
        $residence->reviews_count === 0 => null,
        $rating >= 9 => 'Exceptionnel',
        $rating >= 8 => 'Très bien',
        $rating >= 7 => 'Bien',
        $rating >= 6 => 'Agréable',
        default => 'Correct',
    };
    $location = collect([$residence->neighborhood ?: $residence->district, $residence->city?->name])->filter()->unique()->implode(', ');
    $equipments = $residence->equipments->take(3);
    $moreEquipments = $residence->equipments->count() - $equipments->count();
    $price = (int) $residence->prix_min;
    $image = $residence->coverImage?->url ?? asset('assets/images/home/residence-1.webp');
    // Les dates et voyageurs recherchés suivent vers la fiche : prix et disponibilités y sont déjà calculés
    $url = route('residences.show', [$residence, ...array_filter(['arrivee' => request('arrivee'), 'depart' => request('depart'), 'adultes' => request('voyageurs')])]);
@endphp

<article class="listing-card">

    <a href="{{ $url }}" class="listing-card-media" aria-label="Voir {{ $residence->name }}">
        <img src="{{ $image }}" alt="{{ $residence->name }}" @if ($index > 2) loading="lazy" @endif>

        <span class="listing-card-badges">
            <span class="listing-badge">
                <i class="fa-solid {{ $residence->propertyType->fa_icon }}"></i>
                {{ $residence->propertyType->name }}
            </span>
            @if ($residence->is_featured)
                <span class="listing-badge listing-badge-gold"><i class="fa-solid fa-crown"></i> Coup de cœur</span>
            @endif
            @if ($residence->en_promo > 0)
                <span class="listing-badge listing-badge-promo"><i class="fa-solid fa-tag"></i> Promo</span>
            @endif
        </span>
    </a>

    @if (auth()->user()?->isClient())
        @php $isFavorite = in_array($residence->id, $favoriteIds ?? [], true); @endphp
        <form method="POST" action="{{ route('client.favorites.toggle', $residence) }}">
            @csrf
            <button type="submit" class="listing-card-fav" aria-label="{{ $isFavorite ? 'Retirer '.$residence->name.' des favoris' : 'Ajouter '.$residence->name.' aux favoris' }}" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}">
                <i class="fa-{{ $isFavorite ? 'solid' : 'regular' }} fa-heart"></i>
            </button>
        </form>
    @elseif (! auth()->check())
        <a href="{{ route('login') }}" class="listing-card-fav" aria-label="Connectez-vous pour ajouter {{ $residence->name }} à vos favoris">
            <i class="fa-regular fa-heart"></i>
        </a>
    @endif

    <div class="listing-card-body">
        <div class="listing-card-top">
            <p class="listing-card-location">
                <i class="fa-solid fa-location-dot"></i>
                {{ $location }}
            </p>

            @if ($ratingLabel)
                <p class="listing-card-rating" title="{{ $residence->reviews_count }} avis">
                    <strong>{{ number_format($rating, 1, ',', ' ') }}</strong>
                    <span>{{ $ratingLabel }}</span>
                </p>
            @else
                <p class="listing-card-rating listing-card-rating-new"><span>Nouveau</span></p>
            @endif
        </div>

        <h3>
            <a href="{{ $url }}">{{ $residence->name }}</a>
        </h3>

        <p class="listing-card-facts">
            <span><i class="fa-solid fa-user-group"></i> Jusqu’à {{ $residence->capacite_max }} voyageurs</span>
            @if ($residence->reviews_count > 0)
                <span><i class="fa-regular fa-comment"></i> {{ $residence->reviews_count }} avis</span>
            @endif
        </p>

        @if ($equipments->isNotEmpty())
            <ul class="listing-card-equipments">
                @foreach ($equipments as $equipment)
                    <li><i class="fa-solid {{ $equipment->fa_icon }}"></i> {{ $equipment->name }}</li>
                @endforeach
                @if ($moreEquipments > 0)
                    <li class="listing-card-more">+{{ $moreEquipments }}</li>
                @endif
            </ul>
        @endif

        <div class="listing-card-footer">
            <div class="listing-card-price">
                <small>À partir de</small>
                <p><strong>{{ number_format($price, 0, ',', ' ') }} FCFA</strong> <span>/ nuit</span></p>
                @if ($nights)
                    <small class="listing-card-total">
                        {{ number_format($price * $nights, 0, ',', ' ') }} FCFA pour {{ $nights }} nuit{{ $nights > 1 ? 's' : '' }}
                    </small>
                @endif
            </div>

            <a href="{{ $url }}" class="listing-card-cta">
                Voir
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

</article>
