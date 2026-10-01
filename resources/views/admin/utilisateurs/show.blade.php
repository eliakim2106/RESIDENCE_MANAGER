@extends('layouts.admin')

@php
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $canManage = auth()->user()->can('manage', $user);
    $canChangeRole = auth()->user()->can('changeRole', $user);
@endphp

@section('title', $user->name)

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <img src="{{ $user->avatarUrl() }}" alt="" class="profile-hero-avatar">
            <div>
                <h1>{{ $user->name }}</h1>
                <p>{{ $user->role->label() }} · inscrit le {{ $user->created_at->translatedFormat('d F Y') }}</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <span class="status-pill status-{{ $user->statut->tone() }} status-pill-lg">{{ $user->statut->label() }}</span>
            <a href="{{ route('admin.utilisateurs.index') }}" class="btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Retour
            </a>
        </div>
    </div>

    @include('partials.flash')

    <div class="resa-layout">

        {{-- ========== Colonne principale ========== --}}
        <div class="resa-main">

            {{-- Chiffres clés selon le rôle --}}
            <div class="payment-totals">
                @if ($user->isOwner())
                    <div class="payment-total">
                        <span class="payment-total-icon tone-info"><i class="fa-solid fa-building"></i></span>
                        <div><span>Établissements</span><strong>{{ $user->properties_count }}</strong></div>
                    </div>
                @else
                    <div class="payment-total">
                        <span class="payment-total-icon tone-info"><i class="fa-solid fa-calendar-check"></i></span>
                        <div><span>Réservations</span><strong>{{ $user->reservations_count }}</strong></div>
                    </div>
                    <div class="payment-total">
                        <span class="payment-total-icon tone-good"><i class="fa-solid fa-wallet"></i></span>
                        <div><span>Montant réglé</span><strong>{{ $money($amountPaid) }}</strong></div>
                    </div>
                @endif
                <div class="payment-total">
                    <span class="payment-total-icon tone-warning"><i class="fa-solid fa-star"></i></span>
                    <div><span>Avis publiés</span><strong>{{ $user->reviews_count }}</strong></div>
                </div>
            </div>

            {{-- Établissements du propriétaire --}}
            @if ($user->isOwner())
                <section class="dash-card">
                    <div class="dash-card-header">
                        <div>
                            <h2>Établissements</h2>
                            <p>Les plus récents</p>
                        </div>
                        @if ($user->properties_count > $user->properties->count())
                            <a href="{{ route('admin.etablissements.index', ['search' => $user->name]) }}" class="dash-link">Tout voir <i class="fa-solid fa-arrow-right"></i></a>
                        @endif
                    </div>

                    @if ($user->properties->isEmpty())
                        <p class="resa-note">Aucun établissement pour le moment.</p>
                    @else
                        <ul class="resa-payments">
                            @foreach ($user->properties as $property)
                                <li>
                                    <span class="cell-icon"><i class="fa-solid fa-building"></i></span>
                                    <span class="resa-unit-text">
                                        <a href="{{ route('admin.etablissements.edit', $property) }}" class="cell-title-link"><strong>{{ $property->name }}</strong></a>
                                        <small>{{ $property->units_count }} unité{{ $property->units_count > 1 ? 's' : '' }}</small>
                                    </span>
                                    <span class="status-pill status-{{ $property->statut->tone() }}">{{ $property->statut->label() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif

            {{-- Réservations du client --}}
            @unless ($user->isOwner() || $user->isAdmin())
                <section class="dash-card">
                    <div class="dash-card-header">
                        <div>
                            <h2>Réservations</h2>
                            <p>Les 5 plus récentes</p>
                        </div>
                        @if ($user->reservations_count > 0)
                            <a href="{{ route('admin.reservations.index', ['search' => $user->name]) }}" class="dash-link">Tout voir <i class="fa-solid fa-arrow-right"></i></a>
                        @endif
                    </div>

                    @if ($user->reservations->isEmpty())
                        <p class="resa-note">Aucune réservation pour le moment.</p>
                    @else
                        <ul class="resa-payments">
                            @foreach ($user->reservations as $reservation)
                                <li>
                                    <span class="cell-icon"><i class="fa-solid fa-receipt"></i></span>
                                    <span class="resa-unit-text">
                                        <a href="{{ route('admin.reservations.show', $reservation) }}" class="cell-title-link"><strong>{{ $reservation->reference }}</strong></a>
                                        <small>{{ $reservation->property?->name }} · {{ $reservation->check_in->format('d/m') }} → {{ $reservation->check_out->format('d/m/Y') }}</small>
                                    </span>
                                    <span class="resa-payment-end">
                                        <span class="resa-unit-amount">{{ $money($reservation->total_amount) }}</span>
                                        <span class="status-pill status-{{ $reservation->statut->tone() }}">{{ $reservation->statut->label() }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endunless

            {{-- Journal des connexions --}}
            <section class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <h2>Dernières connexions</h2>
                        <p>Tentatives réussies et échouées</p>
                    </div>
                    <a href="{{ route('admin.utilisateurs.connexions', ['search' => $user->email]) }}" class="dash-link">Journal complet <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                @include('admin.utilisateurs.partials.login-list', ['logins' => $user->loginLogs])
            </section>
        </div>

        {{-- ========== Colonne latérale ========== --}}
        <aside class="resa-aside">
            <section class="dash-card">
                <div class="dash-card-header">
                    <h2>Informations</h2>
                </div>

                <dl class="resa-info">
                    <div>
                        <dt>Email</dt>
                        <dd>
                            <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                            @if ($user->hasVerifiedEmail())
                                <span class="text-tone-good"><i class="fa-solid fa-circle-check"></i> confirmé</span>
                            @else
                                <span class="text-tone-warning"><i class="fa-solid fa-triangle-exclamation"></i> non confirmé</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Téléphone</dt>
                        <dd>
                            @if ($user->phone)
                                <a href="tel:{{ $user->internationalPhone() }}">{{ $user->formattedPhone() }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    @if ($user->company_name)
                        <div>
                            <dt>Entreprise</dt>
                            <dd>{{ $user->company_name }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt>Localisation</dt>
                        <dd>{{ collect([$user->city, $user->country])->filter()->implode(', ') ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>Dernière connexion</dt>
                        <dd>{{ $user->last_login_at?->translatedFormat('d F Y à H:i') ?? 'Jamais' }}</dd>
                    </div>
                </dl>
            </section>

            @if ($canManage || $canChangeRole)
                <section class="dash-card">
                    <div class="dash-card-header">
                        <h2>Gestion du compte</h2>
                    </div>

                    <div class="resa-actions">
                        @if ($canChangeRole)
                            <form method="POST" action="{{ route('admin.utilisateurs.role', $user) }}" class="role-form">
                                @csrf
                                @method('PATCH')
                                <div class="form-group">
                                    <label for="role">Rôle</label>
                                    <select name="role" id="role">
                                        @foreach ($roles as $value => $label)
                                            <option value="{{ $value }}" @selected($user->role->value === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn-secondary">
                                    <i class="fa-solid fa-user-gear"></i>
                                    Changer le rôle
                                </button>
                            </form>
                        @endif

                        @if ($canManage)
                            @if ($user->statut === App\Enums\UserStatus::Suspended)
                                <form method="POST" action="{{ route('admin.utilisateurs.reactivate', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-primary">
                                        <i class="fa-solid fa-user-check"></i>
                                        Réactiver le compte
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.utilisateurs.suspend', $user) }}"
                                    data-confirm="Suspendre le compte de {{ $user->name }} ? Il ne pourra plus se connecter.">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-outline-danger">
                                        <i class="fa-solid fa-user-slash"></i>
                                        Suspendre le compte
                                    </button>
                                </form>
                                <p class="resa-hint">
                                    <i class="fa-solid fa-circle-info"></i>
                                    Un compte suspendu est déconnecté immédiatement. Ses réservations et établissements sont conservés.
                                </p>
                            @endif
                        @endif
                    </div>
                </section>
            @elseif (auth()->user()->is($user))
                <section class="dash-card">
                    <p class="resa-note">C’est votre compte : modifiez-le depuis <a href="{{ route('admin.profil.edit') }}">Mon profil</a>.</p>
                </section>
            @endif
        </aside>
    </div>
@endsection
