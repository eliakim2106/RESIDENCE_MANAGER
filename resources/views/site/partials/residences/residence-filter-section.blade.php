@php
    [$priceFloor, $priceCeil] = $priceBounds;
    $minPrice = $filters['prix_min'] ?? $priceFloor;
    $maxPrice = $filters['prix_max'] ?? $priceCeil;
@endphp

{{-- Colonne de filtres sur grand écran, tiroir (offcanvas) en dessous de 992 px --}}
<aside class="listing-filters offcanvas-lg offcanvas-bottom" id="listingFilters" tabindex="-1" aria-labelledby="listingFiltersTitle">

    <div class="listing-filters-header">
        <h2 id="listingFiltersTitle">Filtres</h2>

        @if ($activeFilters !== [])
            <a href="{{ route('residences.index') }}" class="listing-link">Tout effacer</a>
        @endif

        <button type="button" class="listing-filters-close" data-bs-dismiss="offcanvas" data-bs-target="#listingFilters" aria-label="Fermer les filtres">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="listing-filters-body">

        {{-- Type d'établissement --}}
        <fieldset class="listing-filter">
            <legend>Type d’établissement</legend>

            <div class="listing-type-grid">
                @foreach ($types as $type)
                    <label class="listing-type">
                        <input type="checkbox" name="types[]" value="{{ $type->slug }}" @checked(in_array($type->slug, $filters['types'], true)) data-auto-submit>
                        <span>
                            <i class="fa-solid {{ $type->fa_icon }}"></i>
                            {{ $type->name }}
                            <small>{{ $type->properties_count }}</small>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        {{-- Budget --}}
        <fieldset class="listing-filter">
            <legend>Budget par nuit</legend>

            <div class="listing-range" data-price-range data-min="{{ $priceFloor }}" data-max="{{ $priceCeil }}">
                <div class="listing-range-track">
                    <span class="listing-range-fill"></span>
                    <input type="range" min="{{ $priceFloor }}" max="{{ $priceCeil }}" step="5000" value="{{ $minPrice }}" aria-label="Prix minimum" data-range-min>
                    <input type="range" min="{{ $priceFloor }}" max="{{ $priceCeil }}" step="5000" value="{{ $maxPrice }}" aria-label="Prix maximum" data-range-max>
                </div>

                <div class="listing-range-inputs">
                    <label>
                        <span>Min</span>
                        <input type="number" name="prix_min" value="{{ $filters['prix_min'] }}" placeholder="{{ number_format($priceFloor, 0, ',', ' ') }}" min="0" step="1000" inputmode="numeric" data-price-min>
                    </label>
                    <span class="listing-range-sep">–</span>
                    <label>
                        <span>Max</span>
                        <input type="number" name="prix_max" value="{{ $filters['prix_max'] }}" placeholder="{{ number_format($priceCeil, 0, ',', ' ') }}" min="0" step="1000" inputmode="numeric" data-price-max>
                    </label>
                </div>
                <p class="listing-hint">Montants en FCFA, promotions incluses.</p>
            </div>
        </fieldset>

        {{-- Ville --}}
        <fieldset class="listing-filter">
            <legend>Ville</legend>

            @foreach ($cities as $city)
                <label class="listing-check">
                    <input type="checkbox" name="villes[]" value="{{ $city->slug }}" @checked(in_array($city->slug, $filters['villes'], true)) data-auto-submit>
                    <span class="listing-check-box"><i class="fa-solid fa-check"></i></span>
                    <span class="listing-check-label">{{ $city->name }}</span>
                    <small>{{ $city->properties_count }}</small>
                </label>
            @endforeach
        </fieldset>

        {{-- Note des clients --}}
        <fieldset class="listing-filter">
            <legend>Note des clients</legend>

            <div class="listing-pills">
                <label class="listing-pill">
                    <input type="radio" name="note" value="" @checked($filters['note'] === null) data-auto-submit>
                    <span>Toutes</span>
                </label>
                @foreach (App\Services\ResidenceSearch::RATINGS as $value => $label)
                    <label class="listing-pill">
                        <input type="radio" name="note" value="{{ $value }}" @checked($filters['note'] === $value) data-auto-submit>
                        <span><i class="fa-solid fa-star"></i> {{ $value }}+ <small>{{ $label }}</small></span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        {{-- Équipements --}}
        <fieldset class="listing-filter">
            <legend>Équipements</legend>

            @foreach ($equipments as $equipment)
                <label class="listing-check">
                    <input type="checkbox" name="equipements[]" value="{{ $equipment->slug }}" @checked(in_array($equipment->slug, $filters['equipements'], true)) data-auto-submit>
                    <span class="listing-check-box"><i class="fa-solid fa-check"></i></span>
                    <span class="listing-check-label"><i class="fa-solid {{ $equipment->fa_icon }}"></i> {{ $equipment->name }}</span>
                </label>
            @endforeach
        </fieldset>

    </div>

    {{-- Sur mobile, les filtres s'appliquent avec ce bouton ; sur grand écran, dès qu'ils changent --}}
    <div class="listing-filters-footer">
        <a href="{{ route('residences.index') }}" class="listing-btn listing-btn-ghost">Réinitialiser</a>
        <button type="submit" class="listing-btn listing-btn-primary">
            Voir les résultats
        </button>
    </div>

</aside>
