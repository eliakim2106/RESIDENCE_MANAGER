@php $hero = $site->content('listing_hero'); @endphp

<section class="listing-hero">
    <img src="{{ $site->image($hero['image'], 'assets/images/home/slide-2.webp') }}" alt="" class="listing-hero-bg" fetchpriority="high">

    <div class="container listing-hero-content">
        <nav aria-label="Fil d’Ariane">
            <ol class="listing-breadcrumb">
                <li><a href="{{ route('home') }}">Accueil</a></li>
                <li aria-current="page">Résidences</li>
            </ol>
        </nav>

        <h1>{{ $hero['title'] }} @if ($hero['highlight'])<span>{{ $hero['highlight'] }}</span>@endif</h1>

        <p>
            {{ $cities->sum('properties_count') }} établissement{{ $cities->sum('properties_count') > 1 ? 's' : '' }} sélectionné{{ $cities->sum('properties_count') > 1 ? 's' : '' }}
            dans {{ $cities->count() }} ville{{ $cities->count() > 1 ? 's' : '' }} de Côte d’Ivoire :
            résidences meublées, hôtels, villas et appartements.
        </p>

        <div class="listing-search">
            <div class="listing-search-field listing-search-field-wide">
                <label for="listing-destination"><i class="fa-solid fa-location-dot"></i> Destination</label>
                <input type="search" id="listing-destination" name="destination" value="{{ $filters['destination'] }}"
                    placeholder="Ville, commune, quartier, nom…" autocomplete="off">
            </div>

            <div class="listing-search-field">
                <label for="listing-arrivee"><i class="fa-regular fa-calendar"></i> Arrivée</label>
                <input type="date" id="listing-arrivee" name="arrivee" value="{{ $filters['arrivee']?->toDateString() }}"
                    min="{{ now()->toDateString() }}" data-placeholder="Ajouter une date"
                    data-datepicker data-datepicker-start="liste">
            </div>

            <div class="listing-search-field">
                <label for="listing-depart"><i class="fa-regular fa-calendar-check"></i> Départ</label>
                <input type="date" id="listing-depart" name="depart" value="{{ $filters['depart']?->toDateString() }}"
                    min="{{ now()->addDay()->toDateString() }}" data-placeholder="Ajouter une date"
                    data-datepicker data-datepicker-end="liste">
            </div>

            <div class="listing-search-field">
                <label for="listing-voyageurs"><i class="fa-solid fa-user-group"></i> Voyageurs</label>
                <select id="listing-voyageurs" name="voyageurs">
                    <option value="">Indifférent</option>
                    @for ($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}" @selected($filters['voyageurs'] === $i)>{{ $i }} voyageur{{ $i > 1 ? 's' : '' }}</option>
                    @endfor
                    <option value="10" @selected($filters['voyageurs'] >= 10)>10 et plus</option>
                </select>
            </div>

            <button type="submit" class="listing-search-btn">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Rechercher</span>
            </button>
        </div>
    </div>
</section>
