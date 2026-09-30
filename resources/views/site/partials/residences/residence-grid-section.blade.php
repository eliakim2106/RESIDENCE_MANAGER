<section class="listing-results" aria-labelledby="listingResultsTitle">

    <div class="listing-toolbar">
        <div class="listing-toolbar-title">
            <h2 id="listingResultsTitle">
                {{ $residences->total() }} {{ $residences->total() > 1 ? 'résidences disponibles' : 'résidence disponible' }}
            </h2>
            <p>
                @if ($nights)
                    Du {{ $filters['arrivee']->translatedFormat('j F') }} au {{ $filters['depart']->translatedFormat('j F Y') }}
                    · {{ $nights }} nuit{{ $nights > 1 ? 's' : '' }}
                @else
                    Ajoutez vos dates pour voir les disponibilités et le prix de votre séjour.
                @endif
            </p>
        </div>

        <div class="listing-toolbar-actions">
            <button type="button" class="listing-filters-toggle" data-bs-toggle="offcanvas" data-bs-target="#listingFilters" aria-controls="listingFilters">
                <i class="fa-solid fa-sliders"></i>
                Filtres
                @if (count($activeFilters) > 0)
                    <span class="listing-count">{{ count($activeFilters) }}</span>
                @endif
            </button>

            <label class="listing-sort">
                <span>Trier par</span>
                <select name="tri" data-auto-submit data-auto-submit-always>
                    @foreach (App\Services\ResidenceSearch::SORTS as $value => $label)
                        <option value="{{ $value }}" @selected($filters['tri'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </div>

    @if ($activeFilters !== [])
        <ul class="listing-chips" aria-label="Filtres appliqués">
            @foreach ($activeFilters as $chip)
                <li>
                    <a href="{{ $chip['url'] }}" class="listing-chip" aria-label="Retirer le filtre {{ $chip['label'] }}">
                        {{ $chip['label'] }}
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                </li>
            @endforeach
            <li>
                <a href="{{ route('residences.index') }}" class="listing-link">Tout effacer</a>
            </li>
        </ul>
    @endif

    @if ($residences->isEmpty())
        <div class="listing-empty">
            <span class="listing-empty-icon"><i class="fa-solid fa-house-circle-xmark"></i></span>
            <h3>Aucune résidence ne correspond à votre recherche</h3>
            <p>Essayez d’autres dates, élargissez votre budget ou retirez quelques filtres.</p>
            <a href="{{ route('residences.index') }}" class="listing-btn listing-btn-primary">
                Voir toutes les résidences
            </a>
        </div>
    @else
        <div class="listing-grid">
            @foreach ($residences as $residence)
                @include('site.partials.residences.residence-card', ['residence' => $residence, 'index' => $loop->index])
            @endforeach
        </div>

        @include('site.partials.residences.pagination', ['paginator' => $residences])
    @endif

</section>
