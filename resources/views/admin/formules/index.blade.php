@extends('layouts.admin')

@php
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
@endphp

@section('title', 'Formules d’abonnement')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-layer-group"></i></span>
            <div>
                <h1>Formules d’abonnement</h1>
                <p>Les offres proposées aux propriétaires, leurs prix et leurs limites.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <a href="{{ route('admin.abonnements.index') }}" class="btn-secondary">
                <i class="fa-solid fa-id-card"></i>
                Abonnements
            </a>
            <a href="{{ route('admin.formules.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i>
                Nouvelle formule
            </a>
        </div>
    </div>

    @include('partials.flash')

    {{-- ========== Réglages ========== --}}
    <form method="POST" action="{{ route('admin.formules.settings') }}" class="plan-settings" data-settings-form>
        @csrf
        @method('PUT')

        <header class="plan-settings-head">
            <span class="plan-settings-icon"><i class="fa-solid fa-sliders"></i></span>
            <div>
                <h2>Règles des abonnements</h2>
                <p>Elles s’appliquent à tous les propriétaires, quelle que soit leur formule.</p>
            </div>
            <span class="plan-settings-dirty" data-dirty-note hidden>
                <i class="fa-solid fa-circle"></i> Modifications non enregistrées
            </span>
        </header>

        <div class="plan-settings-grid">
            {{-- Abonnement obligatoire --}}
            <input type="hidden" name="obligatoire" value="0">
            <label class="setting-tile is-switch">
                <input type="checkbox" name="obligatoire" value="1" @checked(old('obligatoire', $required)) data-required-toggle>
                <span class="setting-tile-icon tone-navy"><i class="fa-solid fa-shield-halved"></i></span>
                <span class="setting-tile-body">
                    <span class="setting-tile-title">
                        <strong>Abonnement obligatoire</strong>
                        <span class="setting-state is-on">Activé</span>
                        <span class="setting-state is-off">Désactivé</span>
                    </span>
                    <small>Sans abonnement en règle, les établissements d’un propriétaire quittent le site et il ne peut plus en ajouter.</small>
                </span>
                <span class="switch-ui" aria-hidden="true"></span>
            </label>

            {{-- Délai de grâce --}}
            <div class="setting-tile {{ $errors->has('delai_grace') ? 'has-error' : '' }}">
                <span class="setting-tile-icon tone-gold"><i class="fa-solid fa-hourglass-half"></i></span>
                <span class="setting-tile-body">
                    <span class="setting-tile-title">
                        <label for="delai_grace"><strong>Délai de grâce</strong></label>
                    </span>
                    <small>Jours laissés pour régler une facture avant la suspension. Un rappel part {{ App\Services\SubscriptionManager::REMINDER_DAYS }} jours avant l’échéance.</small>
                    @error('delai_grace') <p class="field-error">{{ $message }}</p> @enderror
                </span>
                <div class="stepper" data-stepper>
                    <button type="button" class="stepper-btn" data-stepper-down aria-label="Retirer un jour"><i class="fa-solid fa-minus"></i></button>
                    <span class="stepper-value">
                        <input type="number" id="delai_grace" name="delai_grace" min="0" max="60" inputmode="numeric" value="{{ old('delai_grace', $graceDays) }}" data-grace-input>
                        <small>jours</small>
                    </span>
                    <button type="button" class="stepper-btn" data-stepper-up aria-label="Ajouter un jour"><i class="fa-solid fa-plus"></i></button>
                </div>
            </div>
        </div>

        <footer class="plan-settings-foot">
            <p class="plan-settings-summary" data-settings-summary aria-live="polite"></p>
            <button type="submit" class="btn-primary" data-settings-save>
                <i class="fa-solid fa-floppy-disk"></i>
                Enregistrer
            </button>
        </footer>
    </form>

    @unless ($required)
        <div class="moderation-banner tone-warning" role="status">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                <strong>L’abonnement n’est pas encore obligatoire</strong>
                <p>Créez vos formules, laissez les propriétaires souscrire (ou abonnez-les depuis la page Abonnements), puis activez l’obligation.</p>
            </div>
        </div>
    @endunless

    {{-- ========== Formules ========== --}}
    @if ($plans->isEmpty())
        <div class="table-card">
            <div class="empty-state">
                <i class="fa-solid fa-layer-group"></i>
                <strong>Aucune formule</strong>
                <span>Créez votre première formule : prix, limites et durée d’essai sont entièrement réglables.</span>
                <a href="{{ route('admin.formules.create') }}" class="btn-primary">Créer une formule</a>
            </div>
        </div>
    @else
        <div class="plan-grid">
            @foreach ($plans as $plan)
                <article class="plan-card {{ $plan->is_featured ? 'is-featured' : '' }} {{ $plan->isActive() ? '' : 'is-inactive' }}">
                    @if ($plan->is_featured)
                        <span class="plan-ribbon">Recommandée</span>
                    @endif

                    <header class="plan-card-head">
                        <h2>{{ $plan->name }}</h2>
                        @unless ($plan->isActive())
                            <span class="status-pill status-neutral">Non proposée</span>
                        @endunless
                    </header>

                    @if ($plan->description)
                        <p class="plan-card-desc">{{ $plan->description }}</p>
                    @endif

                    <div class="plan-price">
                        @if ($plan->monthly_price === 0)
                            <strong>Gratuit</strong>
                        @else
                            <strong>{{ $money($plan->monthly_price) }}</strong>
                            <span>/ mois</span>
                        @endif
                    </div>
                    @if ($plan->yearly_price !== null)
                        <p class="plan-yearly">
                            ou {{ $money($plan->yearly_price) }} / an
                            @if ($plan->yearlySavingMonths() > 0)
                                <span class="plan-saving">{{ $plan->yearlySavingMonths() }} mois offerts</span>
                            @endif
                        </p>
                    @endif

                    <ul class="plan-features">
                        <li><i class="fa-solid fa-building"></i> {{ $plan->max_properties === null ? 'Établissements illimités' : $plan->max_properties.' établissement'.($plan->max_properties > 1 ? 's' : '') }}</li>
                        <li><i class="fa-solid fa-door-open"></i> {{ $plan->max_units === null ? 'Unités illimitées' : $plan->max_units.' unité'.($plan->max_units > 1 ? 's' : '') }}</li>
                        @if ($plan->trial_days > 0)
                            <li><i class="fa-solid fa-gift"></i> {{ $plan->trial_days }} jours d’essai gratuit</li>
                        @endif
                        @if ((float) $plan->commission_rate > 0)
                            <li><i class="fa-solid fa-percent"></i> Commission de {{ rtrim(rtrim(number_format((float) $plan->commission_rate, 2, ',', ' '), '0'), ',') }} % sur les réservations</li>
                        @endif
                        @foreach ($plan->features ?? [] as $feature)
                            <li><i class="fa-solid fa-check"></i> {{ $feature }}</li>
                        @endforeach
                    </ul>

                    <footer class="plan-card-foot">
                        <span class="plan-subscribers">
                            <i class="fa-solid fa-users"></i>
                            {{ $plan->subscribers_count }} abonné{{ $plan->subscribers_count > 1 ? 's' : '' }}
                        </span>
                        <div class="row-actions">
                            <a href="{{ route('admin.formules.edit', $plan) }}" class="action-btn edit" title="Modifier" aria-label="Modifier {{ $plan->name }}">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <button type="button" class="action-btn delete delete-btn" title="Supprimer" aria-label="Supprimer {{ $plan->name }}"
                                data-url="{{ route('admin.formules.destroy', $plan) }}" data-name="{{ $plan->name }}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </footer>
                </article>
            @endforeach
        </div>
    @endif

@endsection
