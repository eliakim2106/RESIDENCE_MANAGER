@extends('site.pages.layout')

@php
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';
    $steps = [
        ['fa-user-plus', 'Créez votre compte', 'Un compte propriétaire, gratuit, en quelques minutes.'],
        ['fa-building', 'Décrivez votre établissement', 'Photos, logements, prix, équipements et conditions de séjour.'],
        ['fa-circle-check', 'Validation par notre équipe', 'Nous vérifions la fiche avant sa mise en ligne.'],
        ['fa-calendar-check', 'Recevez des réservations', 'Confirmez les demandes, suivez les paiements et vos reversements.'],
    ];
    $features = [
        ['fa-calendar-days', 'Calendrier et disponibilités', 'Tarifs par période, prix du week-end, fermetures et blocages, maintenances.'],
        ['fa-credit-card', 'Paiement en ligne', 'Vos clients règlent par Mobile Money ou carte ; les sommes vous sont reversées.'],
        ['fa-bell', 'Notifications', 'Nouvelle demande, paiement reçu, arrivées du jour : vous êtes prévenu.'],
        ['fa-file-excel', 'Exports Excel', 'Réservations, paiements et reversements exportables à tout moment.'],
        ['fa-star', 'Avis vérifiés', 'Seuls les voyageurs ayant séjourné chez vous peuvent vous noter.'],
        ['fa-mobile-screen', 'Sur tous les écrans', 'Gérez votre activité depuis un ordinateur comme depuis un téléphone.'],
    ];
@endphp

@section('title', 'Propriétaires')
@section('description', 'Proposez votre résidence, votre hôtel ou votre villa sur DS HOLDING : réservations et paiements en ligne, calendrier, reversements.')
@section('page_kicker', 'Propriétaires et gestionnaires')
@section('page_title', 'Proposez votre établissement sur DS HOLDING')
@section('page_lead', 'Résidences, hôtels, villas, appartements : recevez des réservations en ligne et gérez tout depuis un seul espace.')

@section('page')
    <div class="container">
        <section class="info-section">
            <h2 class="info-title">Comment ça marche</h2>
            <ol class="info-steps">
                @foreach ($steps as $index => [$icon, $title, $text])
                    <li>
                        <span class="info-step-icon"><i class="fa-solid {{ $icon }}"></i><b>{{ $index + 1 }}</b></span>
                        <strong>{{ $title }}</strong>
                        <p>{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="info-section">
            <h2 class="info-title">Votre espace de gestion</h2>
            <div class="info-features">
                @foreach ($features as [$icon, $title, $text])
                    <article>
                        <i class="fa-solid {{ $icon }}"></i>
                        <h3>{{ $title }}</h3>
                        <p>{{ $text }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        @if ($plans->isNotEmpty())
            <section class="info-section" id="formules">
                <h2 class="info-title">Formules</h2>
                <div class="info-plans">
                    @foreach ($plans as $plan)
                        <article class="info-plan {{ $plan->is_featured ? 'is-featured' : '' }}">
                            @if ($plan->is_featured)
                                <span class="info-plan-badge">Recommandée</span>
                            @endif
                            <h3>{{ $plan->name }}</h3>
                            @if ($plan->description)
                                <p class="info-plan-text">{{ $plan->description }}</p>
                            @endif
                            <p class="info-plan-price">
                                <strong>{{ $money($plan->monthly_price) }}</strong> <span>/ mois</span>
                            </p>
                            @if ($plan->offersYearly() && $plan->yearlySavingMonths() > 0)
                                <p class="info-plan-yearly">ou {{ $money($plan->yearly_price) }} / an ({{ $plan->yearlySavingMonths() }} mois offerts)</p>
                            @endif
                            <ul>
                                @if ($plan->trial_days)
                                    <li><i class="fa-solid fa-gift"></i> {{ $plan->trial_days }} jours d’essai</li>
                                @endif
                                <li><i class="fa-solid fa-building"></i> {{ $plan->max_properties ? $plan->max_properties.' établissement'.($plan->max_properties > 1 ? 's' : '') : 'Établissements illimités' }}</li>
                                <li><i class="fa-solid fa-door-open"></i> {{ $plan->max_units ? $plan->max_units.' unités' : 'Unités illimitées' }}</li>
                                @foreach ($plan->features ?? [] as $feature)
                                    <li><i class="fa-solid fa-check"></i> {{ $feature }}</li>
                                @endforeach
                            </ul>
                            <a href="{{ route('register.owner') }}" class="site-btn {{ $plan->is_featured ? 'site-btn-gold' : 'site-btn-ghost-dark' }}">Commencer</a>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="info-cta info-cta-dark">
            <div>
                <strong>Prêt à recevoir vos premières réservations ?</strong>
                <span>Créez votre compte propriétaire ; une question avant de vous lancer ? Écrivez-nous.</span>
            </div>
            <div class="info-cta-actions">
                <a href="{{ route('pages.contact', ['sujet' => 'Partenariat propriétaire']) }}" class="site-btn site-btn-ghost">Nous contacter</a>
                <a href="{{ route('register.owner') }}" class="site-btn site-btn-gold">Créer mon compte <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
@endsection
