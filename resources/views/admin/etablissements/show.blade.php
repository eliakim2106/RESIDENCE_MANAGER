@extends('layouts.admin')

@php
    use App\Enums\ActiveStatus;
    use App\Enums\PropertyStatus;
    use App\Enums\ReservationStatus;

    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $status = $etablissement->statut;
    $owner = $etablissement->owner;
    $subscription = $owner?->currentSubscription;
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $hour = fn (?string $time): ?string => $time ? substr($time, 0, 5) : null;
    $cover = $etablissement->images->first();
    $trend = $kpis['revenuePrevious'] > 0 ? round(($kpis['revenue'] - $kpis['revenuePrevious']) / $kpis['revenuePrevious'] * 100) : null;
    $policy = $etablissement->cancellation_policy;
    $policyTone = match ($policy?->value) {
        'flexible' => 'good',
        'moderate' => 'warning',
        default => 'critical',
    };
@endphp

@section('title', $etablissement->name)

@section('content')
    {{-- ========== En-tête ========== --}}
    <header class="resa-header etab-header">
        <a href="{{ route('admin.etablissements.index') }}" class="resa-back">
            <i class="fa-solid fa-arrow-left"></i>
            Établissements
        </a>

        <div class="resa-header-main">
            <div class="cell-entity">
                @if ($cover)
                    <img src="{{ $cover->url }}" alt="" class="etab-header-cover">
                @else
                    <span class="etab-header-cover is-empty"><i class="fa-solid {{ $etablissement->propertyType->fa_icon }}"></i></span>
                @endif
                <div class="resa-header-title">
                    <div class="resa-header-line">
                        <h1 class="plain-title">{{ $etablissement->name }}</h1>
                        <span class="status-pill status-{{ $status->tone() }} status-pill-lg">{{ $etablissement->wasRejected() ? 'Refusé' : $status->label() }}</span>
                    </div>
                    <p>
                        <i class="fa-solid {{ $etablissement->propertyType->fa_icon }}"></i> {{ $etablissement->propertyType->name }}
                        @if ($etablissement->star_rating)
                            <span class="etab-stars">{{ str_repeat('★', $etablissement->star_rating) }}</span>
                        @endif
                        <span class="dot-sep">·</span>
                        <i class="fa-solid fa-location-dot"></i> {{ $etablissement->city->name }}{{ $etablissement->district ? ', '.$etablissement->district : '' }}
                        @if ($isAdmin && $owner)
                            <span class="dot-sep">·</span>
                            <i class="fa-solid fa-user-tie"></i> <a href="{{ route('admin.utilisateurs.show', $owner) }}">{{ $owner->name }}</a>
                        @endif
                    </p>
                </div>
            </div>

            <div class="resa-actionbar">
                <a href="{{ route('admin.etablissements.edit', $etablissement) }}" class="btn-secondary">
                    <i class="fa-solid fa-pen"></i> Modifier
                </a>

                {{-- Publication selon le rôle et le statut --}}
                @if ($isAdmin)
                    @if ($status === PropertyStatus::Pending)
                        <button type="button" class="btn-outline-danger" data-modal-open="rejectModal"><i class="fa-solid fa-xmark"></i> Refuser</button>
                        <form method="POST" action="{{ route('admin.etablissements.publish', $etablissement) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i> Valider et publier</button>
                        </form>
                    @elseif ($status === PropertyStatus::Draft)
                        <form method="POST" action="{{ route('admin.etablissements.publish', $etablissement) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-primary"><i class="fa-solid fa-globe"></i> Publier</button>
                        </form>
                    @elseif ($status === PropertyStatus::Published)
                        <button type="button" class="btn-outline-danger" data-modal-open="suspendModal"><i class="fa-solid fa-ban"></i> Suspendre</button>
                        <form method="POST" action="{{ route('admin.etablissements.unpublish', $etablissement) }}" data-confirm="Mettre « {{ $etablissement->name }} » hors ligne ? Il n’apparaîtra plus sur le site ; les réservations existantes sont conservées.">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-secondary"><i class="fa-solid fa-eye-slash"></i> Mettre hors ligne</button>
                        </form>
                    @elseif ($status === PropertyStatus::Suspended)
                        <form method="POST" action="{{ route('admin.validations.reinstate', $etablissement) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-primary"><i class="fa-solid fa-rotate-left"></i> Rétablir</button>
                        </form>
                    @endif
                @else
                    @if ($status === PropertyStatus::Draft)
                        <form method="POST" action="{{ route('admin.etablissements.submit', $etablissement) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-primary"><i class="fa-solid fa-paper-plane"></i> {{ $etablissement->wasRejected() ? 'Soumettre à nouveau' : 'Soumettre pour validation' }}</button>
                        </form>
                    @elseif (in_array($status, [PropertyStatus::Published, PropertyStatus::Pending], true))
                        <form method="POST" action="{{ route('admin.etablissements.unpublish', $etablissement) }}"
                            data-confirm="{{ $status === PropertyStatus::Pending ? 'Retirer la demande de publication ? L’établissement repasse en brouillon.' : 'Mettre « '.$etablissement->name.' » hors ligne ? Il n’apparaîtra plus sur le site ; les réservations existantes sont conservées.' }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-secondary"><i class="fa-solid fa-eye-slash"></i> {{ $status === PropertyStatus::Pending ? 'Retirer la demande' : 'Mettre hors ligne' }}</button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </header>

    @include('partials.flash')

    {{-- ========== Modération ========== --}}
    @if ($status === PropertyStatus::Pending)
        <div class="moderation-banner tone-warning" role="status">
            <i class="fa-solid fa-hourglass-half"></i>
            <div>
                <strong>En attente de validation</strong>
                <p>Soumis {{ $etablissement->submitted_at?->diffForHumans() ?? '' }}. {{ $isAdmin ? 'Vérifiez les informations, les photos et les unités avant de publier.' : 'Il sera visible sur le site dès qu’un administrateur l’aura approuvé.' }}</p>
            </div>
        </div>
    @elseif ($etablissement->wasRejected() || $etablissement->isSuspended())
        <div class="moderation-banner tone-critical" role="status">
            <i class="fa-solid {{ $etablissement->isSuspended() ? 'fa-ban' : 'fa-circle-xmark' }}"></i>
            <div>
                <strong>{{ $etablissement->isSuspended() ? 'Établissement suspendu' : 'Demande de publication refusée' }}{{ $etablissement->moderated_at ? ' le '.$etablissement->moderated_at->translatedFormat('d F Y') : '' }}{{ $isAdmin && $etablissement->moderator ? ' par '.$etablissement->moderator->name : '' }}</strong>
                <p>{{ $etablissement->moderation_note }}</p>
            </div>
        </div>
    @elseif ($status === PropertyStatus::Draft)
        <div class="moderation-banner tone-info" role="status">
            <i class="fa-solid fa-pen-ruler"></i>
            <div>
                <strong>Brouillon : invisible sur le site</strong>
                <p>{{ $isAdmin ? 'Publiez-le quand il est prêt.' : 'Complétez la fiche puis soumettez-la : un administrateur la vérifiera avant la mise en ligne.' }}</p>
            </div>
        </div>
    @endif

    {{-- ========== Indicateurs ========== --}}
    <div class="resa-today">
        <a href="{{ route('admin.reservations.calendar', ['etablissement' => $etablissement->slug]) }}" class="resa-today-card tone-info">
            <span class="resa-today-icon"><i class="fa-solid fa-chart-pie"></i></span>
            <span class="resa-today-text">
                <strong>{{ number_format($kpis['occupancy'], 1, ',', ' ') }} %</strong>
                <span>Occupation en {{ now()->translatedFormat('F') }}</span>
            </span>
        </a>
        <a href="{{ route('admin.paiements.index', ['etablissement' => $etablissement->slug, 'periode' => 'mois']) }}" class="resa-today-card tone-gold">
            <span class="resa-today-icon"><i class="fa-solid fa-sack-dollar"></i></span>
            <span class="resa-today-text">
                <strong class="is-amount">{{ $money($kpis['revenue']) }}</strong>
                <span>
                    Encaissé ce mois-ci
                    @if ($trend !== null)
                        · <em class="{{ $trend >= 0 ? 'text-tone-good' : 'text-tone-critical' }}">{{ $trend >= 0 ? '+' : '' }}{{ $trend }} %</em>
                    @endif
                </span>
            </span>
        </a>
        <a href="{{ route('admin.reservations.index', ['etablissement' => $etablissement->slug, 'statut' => $kpis['pending'] > 0 ? 'en-attente' : 'confirmees']) }}"
            class="resa-today-card tone-good {{ $kpis['pending'] > 0 ? 'has-alert' : '' }}">
            <span class="resa-today-icon"><i class="fa-solid fa-calendar-check"></i></span>
            <span class="resa-today-text">
                <strong>{{ $kpis['upcoming'] }}</strong>
                <span>À venir{{ $kpis['pending'] > 0 ? ' · '.$kpis['pending'].' à valider' : '' }}{{ $kpis['inHouse'] > 0 ? ' · '.$kpis['inHouse'].' en séjour' : '' }}</span>
            </span>
        </a>
        <div class="resa-today-card tone-warning">
            <span class="resa-today-icon"><i class="fa-solid fa-star"></i></span>
            <span class="resa-today-text">
                <strong>{{ $kpis['reviews'] > 0 ? number_format($kpis['rating'], 1, ',', ' ').' / 10' : '—' }}</strong>
                <span>{{ $kpis['reviews'] }} avis client{{ $kpis['reviews'] > 1 ? 's' : '' }} · {{ number_format((int) $etablissement->likes_count, 0, ',', ' ') }} j’aime</span>
            </span>
        </div>
    </div>

    <div class="resa-layout">
        <div class="resa-main">

            {{-- Galerie --}}
            @if ($etablissement->images->isNotEmpty())
                <section class="dash-card etab-gallery-card">
                    <div class="etab-gallery">
                        @foreach ($etablissement->images->take(5) as $image)
                            <img src="{{ $image->url }}" alt="{{ $image->caption ?? $etablissement->name }}" class="{{ $loop->first ? 'is-main' : '' }}" loading="lazy">
                        @endforeach
                        @if ($etablissement->images->count() > 5)
                            <a href="{{ route('admin.etablissements.edit', ['etablissement' => $etablissement, 'etape' => 'medias']) }}" class="etab-gallery-more">
                                +{{ $etablissement->images->count() - 5 }} photo{{ $etablissement->images->count() - 5 > 1 ? 's' : '' }}
                            </a>
                        @endif
                    </div>
                </section>
            @endif

            {{-- Unités (ou, pour un logement entier, son unité unique réglée dans le formulaire) --}}
            @php $wholeHomeForm = route('admin.etablissements.edit', ['etablissement' => $etablissement, 'etape' => 'accueil']); @endphp
            <section class="dash-card">
                <div class="dash-card-header">
                    <div>
                        @if ($etablissement->manages_units)
                            <h2>Unités</h2>
                            <p>{{ $units->count() }} unité{{ $units->count() > 1 ? 's' : '' }} · {{ $units->where('statut', ActiveStatus::Active)->sum('quantity') }} disponible{{ $units->where('statut', ActiveStatus::Active)->sum('quantity') > 1 ? 's' : '' }} à la réservation</p>
                        @else
                            <h2>Logement</h2>
                            <p>Logement loué en entier : son prix, sa capacité et ses équipements se règlent dans le formulaire de l’établissement.</p>
                        @endif
                    </div>
                    @if ($etablissement->manages_units)
                        <a href="{{ route('admin.etablissements.unites.create', $etablissement) }}" class="btn-secondary btn-sm"><i class="fa-solid fa-plus"></i> Ajouter</a>
                    @else
                        <a href="{{ $wholeHomeForm }}" class="btn-secondary btn-sm"><i class="fa-solid fa-pen"></i> Modifier</a>
                    @endif
                </div>

                @if ($units->isEmpty())
                    <div class="etab-empty">
                        <i class="fa-solid fa-door-open"></i>
                        <div>
                            @if ($etablissement->manages_units)
                                <strong>Aucune unité pour le moment</strong>
                                <p>Ajoutez les chambres, appartements ou villas proposés : sans unité, l’établissement ne peut pas recevoir de réservation.</p>
                            @else
                                <strong>Logement à décrire</strong>
                                <p>Indiquez le type, la capacité et le prix d’une nuit dans le formulaire (étape Accueil & conditions) : sans cela, l’établissement ne peut pas recevoir de réservation.</p>
                            @endif
                        </div>
                    </div>
                @else
                    <ul class="etab-unit-list">
                        @foreach ($units as $unit)
                            <li class="{{ $unit->statut === ActiveStatus::Active ? '' : 'is-inactive' }}">
                                @if ($unit->images->first())
                                    <img src="{{ $unit->images->first()->url }}" alt="" loading="lazy">
                                @else
                                    <span class="cell-icon"><i class="fa-solid fa-bed"></i></span>
                                @endif
                                <span class="etab-unit-text">
                                    <strong>{{ $unit->name }}</strong>
                                    <small>
                                        {{ $unit->unitType?->name }}
                                        · {{ $unit->max_adults + $unit->max_children }} pers.
                                        @if ($unit->quantity > 1)
                                            · {{ $unit->quantity }} exemplaires
                                        @endif
                                    </small>
                                </span>
                                <span class="etab-unit-price">
                                    @if ($unit->promo_price && $unit->promo_price < $unit->base_price)
                                        <del>{{ $money($unit->base_price) }}</del>
                                        <strong>{{ $money($unit->promo_price) }}</strong>
                                    @else
                                        <strong>{{ $money($unit->base_price) }}</strong>
                                    @endif
                                    <small>/ nuit</small>
                                </span>
                                <span class="status-pill status-{{ $unit->statut->tone() }}">{{ $unit->statut->label() }}</span>
                                <a href="{{ $etablissement->manages_units ? route('admin.unites.edit', $unit) : $wholeHomeForm }}" class="action-btn edit" title="Modifier" aria-label="Modifier {{ $unit->name }}"><i class="fa-solid fa-pen"></i></a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Prochaines arrivées --}}
            <section class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <h2>Prochaines arrivées</h2>
                        <p>Réservations en attente ou confirmées</p>
                    </div>
                    <a href="{{ route('admin.reservations.index', ['etablissement' => $etablissement->slug]) }}" class="btn-secondary btn-sm">Toutes les réservations</a>
                </div>

                @if ($arrivals->isEmpty())
                    <p class="resa-note">Aucune arrivée prévue pour le moment.</p>
                @else
                    <ul class="arrival-list">
                        @foreach ($arrivals as $reservation)
                            <li>
                                <span class="arrival-date">
                                    <strong>{{ $reservation->check_in->format('d') }}</strong>
                                    <small>{{ $reservation->check_in->translatedFormat('M') }}</small>
                                </span>
                                <span class="arrival-text">
                                    <a href="{{ route('admin.reservations.show', $reservation) }}" class="cell-title-link"><strong>{{ $reservation->guest_name }}</strong></a>
                                    <small>
                                        {{ $reservation->reference }} · {{ $reservation->nights }} nuit{{ $reservation->nights > 1 ? 's' : '' }}
                                        · {{ $reservation->items->map(fn ($item) => $item->unit?->name)->filter()->implode(', ') }}
                                    </small>
                                </span>
                                <span class="status-pill status-{{ $reservation->statut->tone() }}">{{ $reservation->statut->label() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Avis --}}
            <section class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <h2>Derniers avis</h2>
                        <p>{{ $kpis['reviews'] }} avis publié{{ $kpis['reviews'] > 1 ? 's' : '' }}</p>
                    </div>
                </div>

                @if ($reviews->isEmpty())
                    <p class="resa-note">Aucun avis pour le moment.</p>
                @else
                    <ul class="etab-reviews">
                        @foreach ($reviews as $review)
                            <li>
                                <span class="etab-review-score">{{ $review->rating }}<small>/10</small></span>
                                <div>
                                    <strong>{{ $review->title ?: 'Avis de '.($review->user?->name ?? 'client') }}</strong>
                                    <p>{{ Str::limit($review->comment, 220) }}</p>
                                    <small>{{ $review->user?->name ?? 'Client' }} · {{ $review->created_at->translatedFormat('d F Y') }}</small>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <aside class="resa-aside">
            {{-- Coordonnées --}}
            <section class="dash-card">
                <div class="dash-card-header">
                    <h2>Coordonnées</h2>
                    <a href="{{ route('admin.etablissements.edit', ['etablissement' => $etablissement, 'etape' => 'localisation']) }}" class="action-btn edit" title="Modifier la localisation" aria-label="Modifier la localisation"><i class="fa-solid fa-pen"></i></a>
                </div>
                <ul class="etab-info-list">
                    <li><i class="fa-solid fa-location-dot"></i> <span>{{ $etablissement->address }}<small>{{ collect([$etablissement->neighborhood, $etablissement->district, $etablissement->city->name])->filter()->implode(', ') }}</small></span></li>
                    @if ($etablissement->phone)
                        <li><i class="fa-solid fa-phone"></i> <a href="tel:{{ $etablissement->internationalPhone() }}">{{ $etablissement->formattedPhone() }}</a></li>
                    @endif
                    @if ($etablissement->email)
                        <li><i class="fa-solid fa-envelope"></i> <a href="mailto:{{ $etablissement->email }}">{{ $etablissement->email }}</a></li>
                    @endif
                    @if ($etablissement->website)
                        <li><i class="fa-solid fa-globe"></i> <a href="{{ $etablissement->website }}" target="_blank" rel="noopener">{{ Str::after($etablissement->website, '://') }}</a></li>
                    @endif
                    @if ($etablissement->latitude && $etablissement->longitude)
                        <li><i class="fa-solid fa-map"></i> <a href="https://www.google.com/maps?q={{ $etablissement->latitude }},{{ $etablissement->longitude }}" target="_blank" rel="noopener">Voir sur la carte</a></li>
                    @endif
                </ul>
            </section>

            {{-- Accueil & conditions --}}
            <section class="dash-card">
                <div class="dash-card-header">
                    <h2>Accueil & conditions</h2>
                    <a href="{{ route('admin.etablissements.edit', ['etablissement' => $etablissement, 'etape' => 'accueil']) }}" class="action-btn edit" title="Modifier les conditions" aria-label="Modifier les conditions"><i class="fa-solid fa-pen"></i></a>
                </div>
                <dl class="resa-amounts">
                    <div>
                        <dt>Arrivée</dt>
                        <dd>{{ $hour($etablissement->check_in_from) ? 'dès '.$hour($etablissement->check_in_from) : '—' }}{{ $hour($etablissement->check_in_until) ? ' jusqu’à '.$hour($etablissement->check_in_until) : '' }}</dd>
                    </div>
                    <div>
                        <dt>Départ</dt>
                        <dd>{{ $hour($etablissement->check_out_until) ? 'avant '.$hour($etablissement->check_out_until) : '—' }}</dd>
                    </div>
                    <div>
                        <dt>Annulation</dt>
                        <dd><span class="status-pill status-{{ $policyTone }}">{{ $policy ? Str::before($policy->label(), ' (') : '—' }}</span></dd>
                    </div>
                </dl>
                @if ($policy)
                    <p class="etab-policy-note">{{ Str::ucfirst(Str::between($policy->label(), '(', ')')) }}.</p>
                @endif
                <ul class="etab-rules">
                    @foreach (['allows_pets' => ['fa-paw', 'Animaux'], 'allows_smoking' => ['fa-smoking', 'Fumeurs'], 'allows_parties' => ['fa-champagne-glasses', 'Fêtes']] as $column => [$icon, $label])
                        <li class="{{ $etablissement->{$column} ? 'is-allowed' : '' }}">
                            <i class="fa-solid {{ $icon }}"></i> {{ $label }}
                            <small>{{ $etablissement->{$column} ? 'autorisés' : 'non autorisés' }}</small>
                        </li>
                    @endforeach
                </ul>
                @if ($etablissement->house_rules)
                    <p class="etab-house-rules">{{ $etablissement->house_rules }}</p>
                @endif
            </section>

            {{-- Abonnement et reversements du propriétaire --}}
            <section class="dash-card">
                <div class="dash-card-header">
                    <h2>{{ $isAdmin ? 'Propriétaire' : 'Abonnement & reversements' }}</h2>
                </div>
                <dl class="resa-amounts">
                    @if ($isAdmin && $owner)
                        <div>
                            <dt>Compte</dt>
                            <dd><a href="{{ route('admin.utilisateurs.show', $owner) }}">{{ $owner->name }}</a></dd>
                        </div>
                    @endif
                    <div>
                        <dt>Formule</dt>
                        <dd>
                            @if ($owner?->isSubscriptionExempt())
                                <span class="status-pill status-info">Exempté</span>
                            @elseif ($subscription)
                                <span class="status-pill status-{{ $subscription->statut->tone() }}">{{ $subscription->plan->name }} · {{ $subscription->statut->label() }}</span>
                            @else
                                <span class="cell-muted">Sans abonnement</span>
                            @endif
                        </dd>
                    </div>
                    @if ($subscription && (float) $subscription->plan->commission_rate > 0)
                        <div>
                            <dt>Commission</dt>
                            <dd>{{ rtrim(rtrim(number_format((float) $subscription->plan->commission_rate, 2, ',', ' '), '0'), ',') }} %</dd>
                        </div>
                    @endif
                    <div>
                        <dt>Reversements</dt>
                        <dd>{{ $owner?->payout_method?->label() ?? 'Non renseignés' }}</dd>
                    </div>
                    <div>
                        <dt>Encaissé au total</dt>
                        <dd>{{ $money($kpis['revenueTotal']) }}</dd>
                    </div>
                </dl>
                <div class="etab-aside-links">
                    @if ($isAdmin)
                        @if ($subscription)
                            <a href="{{ route('admin.abonnements.show', $subscription) }}">Abonnement <i class="fa-solid fa-arrow-right"></i></a>
                        @endif
                        @if ($owner)
                            <a href="{{ route('admin.reversements.owner', $owner) }}">Reversements <i class="fa-solid fa-arrow-right"></i></a>
                        @endif
                    @else
                        <a href="{{ route('admin.abonnement.show') }}">Mon abonnement <i class="fa-solid fa-arrow-right"></i></a>
                        <a href="{{ route('admin.mes-reversements.index') }}">Mes reversements <i class="fa-solid fa-arrow-right"></i></a>
                    @endif
                </div>
            </section>

            {{-- Historique et suppression --}}
            <section class="dash-card">
                <div class="dash-card-header">
                    <h2>Historique</h2>
                </div>
                <dl class="resa-amounts">
                    <div>
                        <dt>Créé le</dt>
                        <dd>{{ $etablissement->created_at->format('d/m/Y') }}</dd>
                    </div>
                    @if ($etablissement->published_at)
                        <div>
                            <dt>Publié le</dt>
                            <dd>{{ $etablissement->published_at->format('d/m/Y') }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt>Modifié</dt>
                        <dd>{{ $etablissement->updated_at->diffForHumans() }}</dd>
                    </div>
                </dl>

                <div class="etab-danger-zone">
                    @if ($activeReservations > 0)
                        <p><i class="fa-solid fa-lock"></i> Suppression impossible : {{ $activeReservations }} réservation{{ $activeReservations > 1 ? 's' : '' }} en attente ou à venir. Mettez plutôt l’établissement hors ligne.</p>
                    @else
                        <button type="button" class="btn-outline-danger btn-sm delete-btn"
                            data-url="{{ route('admin.etablissements.destroy', $etablissement) }}"
                            data-name="{{ $etablissement->name }}"
                            data-detail="{{ $units->isNotEmpty() ? 'Ses '.$units->count().' unité(s) et ses photos seront retirées du site.' : 'Ses photos seront retirées du site.' }}">
                            <i class="fa-solid fa-trash"></i> Supprimer l’établissement
                        </button>
                    @endif
                </div>
            </section>
        </aside>
    </div>

    {{-- ========== Modales de modération (administrateurs) ========== --}}
    @if ($isAdmin)
        @foreach (['rejectModal' => ['reject', 'Refuser la publication', 'fa-xmark', 'Expliquez au propriétaire ce qu’il doit corriger : il recevra ce motif.', 'Refuser'], 'suspendModal' => ['suspend', 'Suspendre l’établissement', 'fa-ban', 'L’établissement quitte le site immédiatement. Le motif est transmis au propriétaire.', 'Suspendre']] as $id => [$action, $title, $icon, $text, $button])
            <div class="modal-overlay" id="{{ $id }}" data-action-modal>
                <div class="modal-card modal-form" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}Title">
                    <div class="modal-icon"><i class="fa-solid {{ $icon }}"></i></div>
                    <h3 id="{{ $id }}Title">{{ $title }}</h3>
                    <p>{{ $text }}</p>
                    <form method="POST" action="{{ route('admin.validations.'.$action, $etablissement) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label for="{{ $id }}Motif">Motif</label>
                            <textarea name="motif" id="{{ $id }}Motif" rows="4" minlength="10" maxlength="1000" required placeholder="Ex : les photos ne correspondent pas à l’établissement décrit."></textarea>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn-cancel" data-modal-close>Retour</button>
                            <button type="submit" class="btn-delete">{{ $button }}</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @endif
@endsection
