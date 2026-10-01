@extends('layouts.site')

@php
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $hour = fn (?string $time): ?string => $time ? substr($time, 0, 5) : null;
    $images = $residence->images;
    $photoCount = $images->count();
    $location = collect([$residence->neighborhood, $residence->district, $residence->city?->name])->filter()->unique()->implode(', ');
    $rating = (float) $residence->rating_average;
    $ratingLabel = match (true) {
        $residence->reviews_count === 0 => null,
        $rating >= 9 => 'Exceptionnel',
        $rating >= 8 => 'Très bien',
        $rating >= 7 => 'Bien',
        default => 'Correct',
    };
    $policy = $residence->cancellation_policy;
    $policyText = match ($policy?->value) {
        'flexible' => ['Flexible', 'Annulation gratuite jusqu’à 24 h avant l’arrivée.'],
        'moderate' => ['Modérée', 'Annulation gratuite jusqu’à 5 jours avant l’arrivée.'],
        'strict' => ['Stricte', 'Non remboursable une fois la réservation confirmée.'],
        default => [null, null],
    };
    $stayQuery = array_filter(['arrivee' => $arrival?->toDateString(), 'depart' => $departure?->toDateString(), 'adultes' => $adults, 'enfants' => $children ?: null]);
    $equipmentGroups = $residence->equipments->groupBy(fn ($equipment) => $equipment->category->label());
    $canBook = $arrival && $units->contains(fn ($line) => ($line['available'] ?? 0) > 0 && $line['issues'] === []);
@endphp

@section('title', $residence->name)

@section('content')
    @include('site.partials.navbar')

    <div class="rd-page">
        <div class="container">

            @unless ($online)
                <div class="rd-preview-banner">
                    <i class="fa-solid fa-eye"></i>
                    Aperçu : cette résidence n’est pas encore visible sur le site.
                </div>
            @endunless

            @include('partials.flash')

            {{-- ========== En-tête ========== --}}
            <nav class="rd-breadcrumb" aria-label="Fil d’Ariane">
                <a href="{{ route('home') }}">Accueil</a>
                <i class="fa-solid fa-chevron-right"></i>
                <a href="{{ route('residences.index') }}">Résidences</a>
                @if ($residence->city)
                    <i class="fa-solid fa-chevron-right"></i>
                    <a href="{{ route('residences.index', ['destination' => $residence->city->name]) }}">{{ $residence->city->name }}</a>
                @endif
            </nav>

            <header class="rd-header">
                <div>
                    <div class="rd-badges">
                        <span class="rd-badge"><i class="fa-solid {{ $residence->propertyType->fa_icon }}"></i> {{ $residence->propertyType->name }}</span>
                        @if ($residence->star_rating)
                            <span class="rd-badge rd-badge-stars" aria-label="{{ $residence->star_rating }} étoiles">{{ str_repeat('★', $residence->star_rating) }}</span>
                        @endif
                        @if ($residence->is_featured)
                            <span class="rd-badge rd-badge-gold"><i class="fa-solid fa-crown"></i> Coup de cœur</span>
                        @endif
                    </div>
                    <h1>{{ $residence->name }}</h1>
                    <p class="rd-location">
                        <i class="fa-solid fa-location-dot"></i> {{ $location }}
                        @if ($residence->reviews_count > 0)
                            <a href="#avis" class="rd-rating-chip"><b>{{ number_format($rating, 1, ',', ' ') }}</b> {{ $ratingLabel }} · {{ $residence->reviews_count }} avis</a>
                        @endif
                    </p>
                </div>

                <div class="rd-header-actions">
                    {{-- J'aime : ouvert à tous ; pour un client, l'établissement rejoint aussi ses favoris --}}
                    <form method="POST" action="{{ route('residences.like', $residence) }}" data-like-form data-like-id="{{ $residence->id }}">
                        @csrf
                        <button type="submit" class="rd-icon-btn rd-like {{ $isLiked ? 'is-active' : '' }}" data-like-button aria-pressed="{{ $isLiked ? 'true' : 'false' }}"
                            @if (auth()->user()?->isClient()) title="Les résidences que vous aimez sont aussi dans vos favoris" @endif>
                            <i class="fa-{{ $isLiked ? 'solid' : 'regular' }} fa-heart"></i>
                            <span>J’aime</span>
                            <span class="rd-like-count" data-like-count @if (! $residence->likes_count) hidden @endif>{{ number_format((int) $residence->likes_count, 0, ',', ' ') }}</span>
                        </button>
                    </form>
                    <button type="button" class="rd-icon-btn" data-share data-share-title="{{ $residence->name }}" data-share-text="{{ $residence->short_description ?: $residence->name.' sur DS HOLDING' }}">
                        <i class="fa-solid fa-share-nodes"></i> <span>Partager</span>
                    </button>
                </div>
            </header>

            {{--
                ========== Galerie ==========
                Grand écran : mosaïque des 5 premières photos. Mobile : carrousel de toutes les photos, au doigt.
                Un clic ouvre la visionneuse plein écran (flèches, clavier, glisser, miniatures).
            --}}
            @if ($images->isNotEmpty())
                <section class="rd-gallery is-count-{{ min($photoCount, 5) }}" data-gallery aria-label="Photos de la résidence">
                    <div class="rd-gallery-grid" data-gallery-track>
                        @foreach ($images as $index => $image)
                            <button type="button" class="rd-gallery-tile" data-gallery-open="{{ $index }}"
                                data-src="{{ $image->url }}" data-caption="{{ $image->caption }}"
                                aria-label="Agrandir la photo {{ $index + 1 }} sur {{ $photoCount }}{{ $image->caption ? ' : '.$image->caption : '' }}">
                                <img src="{{ $image->url }}" alt="{{ $image->caption ?: $residence->name }}"
                                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}" fetchpriority="{{ $index === 0 ? 'high' : 'auto' }}">
                            </button>
                        @endforeach
                    </div>

                    @if ($photoCount > 1)
                        <button type="button" class="rd-gallery-all" data-gallery-open="0">
                            <i class="fa-solid fa-table-cells-large"></i>
                            Voir les {{ $photoCount }} photos
                        </button>
                        <span class="rd-gallery-counter" data-gallery-counter aria-hidden="true">1 / {{ $photoCount }}</span>
                    @endif

                    <dialog class="rd-lightbox" data-lightbox aria-label="Photos de {{ $residence->name }}">
                        <div class="rd-lightbox-bar">
                            <span class="rd-lightbox-counter" data-lightbox-counter>1 / {{ $photoCount }}</span>
                            <strong class="rd-lightbox-title">{{ $residence->name }}</strong>
                            <button type="button" class="rd-lightbox-close" data-lightbox-close aria-label="Fermer">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <figure class="rd-lightbox-stage" data-lightbox-stage>
                            <img src="{{ $images->first()->url }}" alt="" data-lightbox-image>
                            <figcaption data-lightbox-caption></figcaption>
                            @if ($photoCount > 1)
                                <button type="button" class="rd-lightbox-nav is-prev" data-lightbox-prev aria-label="Photo précédente"><i class="fa-solid fa-chevron-left"></i></button>
                                <button type="button" class="rd-lightbox-nav is-next" data-lightbox-next aria-label="Photo suivante"><i class="fa-solid fa-chevron-right"></i></button>
                            @endif
                        </figure>

                        @if ($photoCount > 1)
                            <div class="rd-lightbox-thumbs" aria-label="Toutes les photos">
                                @foreach ($images as $index => $image)
                                    <button type="button" class="rd-lightbox-thumb" data-lightbox-thumb="{{ $index }}" aria-label="Photo {{ $index + 1 }}">
                                        <img src="{{ $image->url }}" alt="" loading="lazy">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </dialog>
                </section>
            @endif

            <div class="rd-layout">
                <div class="rd-main">

                    {{-- ========== En bref ========== --}}
                    <section class="rd-card rd-highlights">
                        <div><i class="fa-solid fa-door-open"></i><strong>{{ $units->count() }} type{{ $units->count() > 1 ? 's' : '' }} de logement</strong><small>{{ $residence->manages_units ? 'Plusieurs unités' : 'Logement entier' }}</small></div>
                        <div><i class="fa-solid fa-user-group"></i><strong>Jusqu’à {{ $units->max(fn ($line) => $line['unit']->max_adults + $line['unit']->max_children) ?? 0 }} voyageurs</strong><small>par logement</small></div>
                        <div><i class="fa-solid fa-key"></i><strong>Arrivée dès {{ $hour($residence->check_in_from) ?? '14:00' }}</strong><small>Départ avant {{ $hour($residence->check_out_until) ?? '12:00' }}</small></div>
                        @if ($policyText[0])
                            <div><i class="fa-solid fa-shield-heart"></i><strong>Annulation {{ mb_strtolower($policyText[0]) }}</strong><small>{{ $policy->value === 'strict' ? 'Non remboursable' : 'Gratuite sous conditions' }}</small></div>
                        @endif
                    </section>

                    {{-- ========== Présentation ========== --}}
                    @if ($residence->description || $residence->short_description)
                        <section class="rd-card">
                            <h2>Présentation</h2>
                            @if ($residence->short_description)
                                <p class="rd-lead">{{ $residence->short_description }}</p>
                            @endif
                            @if ($residence->description)
                                <div class="rd-description" data-collapsible>
                                    <div class="rd-description-text">{!! nl2br(e($residence->description)) !!}</div>
                                    <button type="button" class="rd-more" data-collapsible-toggle hidden>Lire la suite <i class="fa-solid fa-chevron-down"></i></button>
                                </div>
                            @endif
                        </section>
                    @endif

                    {{-- ========== Équipements ========== --}}
                    @if ($residence->equipments->isNotEmpty())
                        <section class="rd-card">
                            <h2>Équipements et services</h2>
                            <div class="rd-equipments" data-collapsible>
                                @foreach ($equipmentGroups as $category => $equipments)
                                    <div class="rd-equipment-group">
                                        <h3>{{ $category }}</h3>
                                        <ul>
                                            @foreach ($equipments as $equipment)
                                                <li><i class="fa-solid {{ $equipment->fa_icon }}"></i> {{ $equipment->name }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- ========== Logements ========== --}}
                    <section class="rd-card" id="logements">
                        <div class="rd-card-head">
                            <div>
                                <h2>Logements</h2>
                                <p>
                                    @if ($arrival)
                                        Prix pour {{ $nights }} nuit{{ $nights > 1 ? 's' : '' }}, du {{ $arrival->translatedFormat('d M') }} au {{ $departure->translatedFormat('d M Y') }}.
                                    @else
                                        Choisissez vos dates pour voir les disponibilités et le prix de votre séjour.
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if ($dateError)
                            <p class="rd-alert"><i class="fa-solid fa-triangle-exclamation"></i> {{ $dateError }}</p>
                        @endif

                        @forelse ($units as $line)
                            @php
                                $unit = $line['unit'];
                                $available = $line['available'];
                                $blocked = $arrival && ($available === 0 || $line['issues'] !== []);
                                $maxQuantity = $arrival ? min((int) $available, 10) : 0;
                            @endphp
                            <article class="rd-unit {{ $blocked ? 'is-unavailable' : '' }}">
                                <div class="rd-unit-media">
                                    <img src="{{ $unit->images->first()?->url ?? $images->first()?->url ?? asset('assets/images/home/residence-1.webp') }}" alt="{{ $unit->name }}" loading="lazy">
                                    @if ($unit->images->count() > 1)
                                        <span>{{ $unit->images->count() }} photos</span>
                                    @endif
                                </div>

                                <div class="rd-unit-body">
                                    <h3>{{ $unit->name }}</h3>
                                    <p class="rd-unit-type">{{ $unit->unitType?->name }}</p>
                                    <ul class="rd-unit-facts">
                                        <li><i class="fa-solid fa-user-group"></i> {{ $unit->max_adults + $unit->max_children }} pers.</li>
                                        @if ($unit->bedrooms)
                                            <li><i class="fa-solid fa-bed"></i> {{ $unit->bedrooms }} chambre{{ $unit->bedrooms > 1 ? 's' : '' }}</li>
                                        @endif
                                        @if ($unit->beds)
                                            <li><i class="fa-solid fa-couch"></i> {{ $unit->beds }} lit{{ $unit->beds > 1 ? 's' : '' }}</li>
                                        @endif
                                        @if ($unit->bathrooms)
                                            <li><i class="fa-solid fa-bath"></i> {{ $unit->bathrooms }} sdb</li>
                                        @endif
                                        @if ($unit->size_m2)
                                            <li><i class="fa-solid fa-ruler-combined"></i> {{ $unit->size_m2 }} m²</li>
                                        @endif
                                    </ul>
                                    @if ($unit->equipments->isNotEmpty())
                                        <p class="rd-unit-equipments">{{ $unit->equipments->take(5)->pluck('name')->implode(' · ') }}{{ $unit->equipments->count() > 5 ? ' · …' : '' }}</p>
                                    @endif
                                    @if ($blocked)
                                        <p class="rd-unit-issue"><i class="fa-solid fa-circle-xmark"></i> {{ $line['issues'][0] ?? 'Complet à ces dates.' }}</p>
                                    @else
                                        @if ($arrival && $available <= 3)
                                            <p class="rd-unit-scarce"><i class="fa-solid fa-fire"></i> Plus que {{ $available }} disponible{{ $available > 1 ? 's' : '' }} à ces dates</p>
                                        @endif
                                        @if ($line['notice'] ?? null)
                                            <p class="rd-unit-notice"><i class="fa-solid fa-circle-info"></i> {{ $line['notice'] }}</p>
                                        @endif
                                    @endif
                                </div>

                                <div class="rd-unit-price">
                                    @if ($arrival && ! $blocked)
                                        <strong>{{ $money($line['subtotal']) }}</strong>
                                        <small>{{ $nights }} nuit{{ $nights > 1 ? 's' : '' }} · {{ $money($line['average']) }} / nuit</small>
                                        @if ($line['cleaning'] > 0)
                                            <small>+ ménage {{ $money($line['cleaning']) }}</small>
                                        @endif
                                        <div class="rd-qty" data-stepper>
                                            <label for="unite-{{ $unit->id }}">Logements</label>
                                            <div class="rd-stepper">
                                                <button type="button" class="rd-stepper-btn" data-step="-1" aria-label="Retirer un logement « {{ $unit->name }} »">
                                                    <i class="fa-solid fa-minus"></i>
                                                </button>
                                                <input type="number" id="unite-{{ $unit->id }}" name="unites[{{ $unit->id }}]" form="bookingForm"
                                                    min="0" max="{{ $maxQuantity }}" step="1" inputmode="numeric"
                                                    value="{{ max(0, min((int) old('unites.'.$unit->id, 0), $maxQuantity)) }}"
                                                    data-unit-select data-subtotal="{{ $line['subtotal'] }}" data-cleaning="{{ $line['cleaning'] }}"
                                                    data-capacity="{{ $unit->max_adults + $unit->max_children }}" data-name="{{ $unit->name }}">
                                                <button type="button" class="rd-stepper-btn" data-step="1" aria-label="Ajouter un logement « {{ $unit->name }} »">
                                                    <i class="fa-solid fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @elseif (! $arrival)
                                        <span class="rd-from">À partir de</span>
                                        <strong>{{ $money($line['average']) }}</strong>
                                        <small>par nuit</small>
                                        <a href="#reservation" class="rd-btn rd-btn-light" data-focus-dates>Voir les disponibilités</a>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="rd-muted">Aucun logement n’est proposé à la réservation pour le moment.</p>
                        @endforelse
                    </section>

                    {{-- ========== Conditions ========== --}}
                    <section class="rd-card">
                        <h2>Conditions de séjour</h2>
                        <div class="rd-conditions">
                            <div>
                                <h3><i class="fa-solid fa-clock"></i> Horaires</h3>
                                <p>Arrivée dès <strong>{{ $hour($residence->check_in_from) ?? '14:00' }}</strong>{{ $hour($residence->check_in_until) ? ' et jusqu’à '.$hour($residence->check_in_until) : '' }}.<br>Départ avant <strong>{{ $hour($residence->check_out_until) ?? '12:00' }}</strong>.</p>
                            </div>
                            @if ($policyText[0])
                                <div>
                                    <h3><i class="fa-solid fa-shield-heart"></i> Annulation {{ mb_strtolower($policyText[0]) }}</h3>
                                    <p>{{ $policyText[1] }}</p>
                                </div>
                            @endif
                            <div>
                                <h3><i class="fa-solid fa-list-check"></i> Règles de la maison</h3>
                                <ul class="rd-rules">
                                    <li class="{{ $residence->allows_pets ? 'is-yes' : '' }}"><i class="fa-solid fa-paw"></i> {{ $residence->allows_pets ? 'Animaux acceptés' : 'Animaux non acceptés' }}</li>
                                    <li class="{{ $residence->allows_smoking ? 'is-yes' : '' }}"><i class="fa-solid fa-smoking"></i> {{ $residence->allows_smoking ? 'Fumeurs autorisés' : 'Non-fumeur' }}</li>
                                    <li class="{{ $residence->allows_parties ? 'is-yes' : '' }}"><i class="fa-solid fa-champagne-glasses"></i> {{ $residence->allows_parties ? 'Fêtes autorisées' : 'Fêtes non autorisées' }}</li>
                                </ul>
                            </div>
                        </div>
                        @if ($residence->house_rules)
                            <p class="rd-house-rules">{{ $residence->house_rules }}</p>
                        @endif
                    </section>

                    {{-- ========== Localisation ========== --}}
                    <section class="rd-card">
                        <h2>Localisation</h2>
                        <p class="rd-muted"><i class="fa-solid fa-location-dot"></i> {{ $residence->address }}, {{ $location }}</p>
                        @if ($residence->latitude && $residence->longitude)
                            <div id="residence-map" class="rd-map" data-latitude="{{ $residence->latitude }}" data-longitude="{{ $residence->longitude }}" data-title="{{ $residence->name }}" data-subtitle="{{ $location }}"></div>
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $residence->latitude }},{{ $residence->longitude }}" target="_blank" rel="noopener" class="rd-link">Ouvrir dans Google Maps <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        @endif
                    </section>

                    {{-- ========== Avis ========== --}}
                    <section class="rd-card" id="avis">
                        <h2>Avis des voyageurs</h2>
                        @if ($residence->reviews_count === 0)
                            <p class="rd-muted">Pas encore d’avis : les voyageurs pourront noter leur séjour après leur départ.</p>
                        @else
                            <div class="rd-review-summary">
                                <div class="rd-review-score">
                                    <strong>{{ number_format($rating, 1, ',', ' ') }}</strong>
                                    <span>{{ $ratingLabel }}</span>
                                    <small>{{ $residence->reviews_count }} avis vérifié{{ $residence->reviews_count > 1 ? 's' : '' }}</small>
                                </div>
                                <ul class="rd-criteria">
                                    @foreach (['proprete' => 'Propreté', 'confort' => 'Confort', 'emplacement' => 'Emplacement', 'accueil' => 'Accueil', 'rapport' => 'Qualité-prix'] as $key => $label)
                                        @if ($criteria?->{$key})
                                            <li>
                                                <span>{{ $label }}</span>
                                                <span class="rd-criteria-bar"><span style="width: {{ round((float) $criteria->{$key} * 10) }}%"></span></span>
                                                <b>{{ number_format((float) $criteria->{$key}, 1, ',', ' ') }}</b>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>

                            <div class="rd-reviews">
                                @foreach ($reviews as $review)
                                    <article class="rd-review">
                                        <header>
                                            <img src="{{ $review->user?->avatarUrl() ?? 'https://ui-avatars.com/api/?name=Client' }}" alt="">
                                            <div>
                                                <strong>{{ $review->authorName() }}</strong>
                                                <small>{{ $review->created_at->translatedFormat('F Y') }}</small>
                                            </div>
                                            <span class="rd-review-note">{{ $review->rating }}<small>/10</small></span>
                                        </header>
                                        @if ($review->title)
                                            <h3>{{ $review->title }}</h3>
                                        @endif
                                        <p>{{ $review->comment }}</p>
                                        @if ($review->owner_reply)
                                            <div class="rd-review-reply">
                                                <strong><i class="fa-solid fa-reply"></i> Réponse de l’établissement</strong>
                                                <p>{{ $review->owner_reply }}</p>
                                            </div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>
                </div>

                {{-- ========== Réservation ========== --}}
                <aside class="rd-sidebar">
                    <section class="rd-booking" id="reservation" tabindex="-1" aria-labelledby="booking-title">
                        <div class="rd-booking-head">
                            <div>
                                <small>{{ $arrival ? 'Votre séjour' : 'À partir de' }}</small>
                                <strong id="booking-title">
                                    @if ($arrival)
                                        {{ $nights }} nuit{{ $nights > 1 ? 's' : '' }}
                                    @else
                                        {{ $money($fromPrice) }} <span>/ nuit</span>
                                    @endif
                                </strong>
                            </div>
                            @if ($residence->reviews_count > 0)
                                <span class="rd-rating-mini"><i class="fa-solid fa-star"></i> {{ number_format($rating, 1, ',', ' ') }}</span>
                            @endif
                        </div>

                        {{-- Dates et voyageurs : recharge la page avec les disponibilités --}}
                        <form method="GET" action="{{ route('residences.show', $residence) }}#logements" class="rd-booking-form">
                            <div class="rd-dates">
                                <label>
                                    <span>Arrivée</span>
                                    <input type="date" name="arrivee" id="booking-check-in" value="{{ $arrival?->toDateString() }}" min="{{ now()->toDateString() }}"
                                        data-datepicker data-datepicker-start="sejour" data-placeholder="Choisir" required>
                                </label>
                                <label>
                                    <span>Départ</span>
                                    <input type="date" name="depart" value="{{ $departure?->toDateString() }}" min="{{ now()->addDay()->toDateString() }}"
                                        data-datepicker data-datepicker-end="sejour" data-placeholder="Choisir" required>
                                </label>
                            </div>
                            <div class="rd-guests">
                                <label>
                                    <span>Adultes</span>
                                    <select name="adultes">
                                        @for ($i = 1; $i <= 12; $i++)
                                            <option value="{{ $i }}" @selected($adults === $i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </label>
                                <label>
                                    <span>Enfants</span>
                                    <select name="enfants">
                                        @for ($i = 0; $i <= 8; $i++)
                                            <option value="{{ $i }}" @selected($children === $i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </label>
                            </div>
                            <button type="submit" class="rd-btn {{ $arrival ? 'rd-btn-light' : 'rd-btn-gold' }} rd-btn-block">
                                <i class="fa-solid fa-magnifying-glass"></i> {{ $arrival ? 'Modifier les dates' : 'Voir les disponibilités' }}
                            </button>
                        </form>

                        {{-- Logements choisis et total : menant au récapitulatif (ou à la création de compte) --}}
                        @if ($arrival)
                            <form method="GET" action="{{ route('residences.checkout', $residence) }}" id="bookingForm" class="rd-summary" data-booking-summary data-service-rate="{{ $serviceRate }}" data-guests="{{ $adults + $children }}">
                                @foreach ($stayQuery as $field => $fieldValue)
                                    <input type="hidden" name="{{ $field }}" value="{{ $fieldValue }}">
                                @endforeach

                                <div class="rd-summary-empty" data-summary-empty>
                                    <i class="fa-solid fa-hand-pointer"></i>
                                    {{ $canBook ? 'Choisissez vos logements dans la liste.' : 'Aucun logement disponible à ces dates : essayez d’autres dates.' }}
                                </div>

                                <div class="rd-summary-lines" data-summary-lines hidden></div>
                                <dl class="rd-summary-totals" data-summary-totals hidden>
                                    <div><dt>Hébergement</dt><dd data-total-subtotal></dd></div>
                                    <div data-total-cleaning-row><dt>Ménage</dt><dd data-total-cleaning></dd></div>
                                    @if ($serviceRate > 0)
                                        <div><dt>Frais de service</dt><dd data-total-service></dd></div>
                                    @endif
                                    <div class="is-total"><dt>Total</dt><dd data-total></dd></div>
                                </dl>
                                <p class="rd-capacity-warning" data-capacity-warning hidden><i class="fa-solid fa-triangle-exclamation"></i> <span></span></p>

                                <button type="submit" class="rd-btn rd-btn-gold rd-btn-block" data-booking-submit disabled>
                                    Réserver <i class="fa-solid fa-arrow-right"></i>
                                </button>
                                @guest
                                    <p class="rd-small">Vous créerez votre compte à l’étape suivante. Aucun paiement n’est demandé pour l’instant.</p>
                                @else
                                    <p class="rd-small">Vous vérifierez votre réservation avant de l’envoyer.</p>
                                @endguest
                            </form>
                        @endif

                        <ul class="rd-booking-trust">
                            <li><i class="fa-solid fa-circle-check"></i> Confirmation par l’établissement</li>
                            <li><i class="fa-solid fa-lock"></i> Paiement en ligne sécurisé</li>
                            @if ($policy && $policy->value !== 'strict')
                                <li><i class="fa-solid fa-rotate-left"></i> Annulation gratuite sous conditions</li>
                            @endif
                        </ul>
                    </section>

                    @if ($residence->phone)
                        <section class="rd-card rd-contact-card">
                            <h3>Une question ?</h3>
                            <p class="rd-muted">L’établissement vous répond directement.</p>
                            <a href="tel:{{ $residence->internationalPhone() }}" class="rd-btn rd-btn-light rd-btn-block"><i class="fa-solid fa-phone"></i> {{ $residence->formattedPhone() }}</a>
                        </section>
                    @endif
                </aside>
            </div>

            {{-- ========== Résidences similaires ========== --}}
            @if ($similar->isNotEmpty())
                <section class="rd-similar">
                    <h2>Vous aimerez aussi</h2>
                    <div class="rd-similar-grid">
                        @foreach ($similar as $other)
                            @php $otherLiked = in_array($other->id, $likedIds, true); @endphp
                            <article class="rd-similar-card">
                                <a href="{{ route('residences.show', [$other, ...$stayQuery]) }}" class="rd-similar-link">
                                    <img src="{{ $other->coverImage?->url ?? asset('assets/images/home/residence-1.webp') }}" alt="{{ $other->name }}" loading="lazy">
                                    <div>
                                        <small>{{ $other->propertyType?->name }} · {{ $other->city?->name }}</small>
                                        <strong>{{ $other->name }}</strong>
                                        <span>
                                            @if ($other->prix_min)
                                                <span><b>{{ $money((int) $other->prix_min) }}</b> / nuit</span>
                                            @endif
                                            @if ($other->reviews_count > 0)
                                                <em><i class="fa-solid fa-star"></i> {{ number_format((float) $other->rating_average, 1, ',', ' ') }}</em>
                                            @endif
                                        </span>
                                    </div>
                                </a>

                                {{-- J'aime : même cœur que sur les cartes de la liste --}}
                                <form method="POST" action="{{ route('residences.like', $other) }}" data-like-form data-like-id="{{ $other->id }}">
                                    @csrf
                                    <button type="submit" class="listing-card-fav {{ $otherLiked ? 'is-active' : '' }}" data-like-button
                                        aria-label="J’aime {{ $other->name }}" aria-pressed="{{ $otherLiked ? 'true' : 'false' }}">
                                        <i class="fa-{{ $otherLiked ? 'solid' : 'regular' }} fa-heart"></i>
                                        <span data-like-count @if (! $other->likes_count) hidden @endif>{{ number_format((int) $other->likes_count, 0, ',', ' ') }}</span>
                                    </button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>

    {{-- Mobile : barre de réservation flottante --}}
    <div class="booking-bar" id="bookingBar">
        <div class="booking-bar-price">
            <span class="booking-bar-amount">
                <strong>{{ $money($fromPrice) }}</strong>
                <span>/ nuit</span>
            </span>
            @if ($residence->reviews_count > 0)
                <small><i class="fa-solid fa-star"></i> {{ number_format($rating, 1, ',', ' ') }} · {{ $residence->reviews_count }} avis</small>
            @endif
        </div>
        <a href="#reservation" class="booking-bar-btn" data-scroll-to-booking>
            <i class="fa-regular fa-calendar-check"></i>
            <span>Réserver</span>
        </a>
    </div>

    @include('site.partials.footer')
@endsection
