@extends('layouts.auth')

@section('title', 'Créer un compte client')

@section('visual_image', 'assets/images/home/slide-3.webp')
@section('visual_kicker', 'Compte client')
@section('visual_title')
    Votre séjour <span>commence ici</span>
@endsection
@section('visual_text', 'Créez votre espace pour réserver en quelques clics et retrouver facilement l’historique de vos séjours.')
@section('visual_points')
    <li><i class="fa-solid fa-magnifying-glass-location"></i> Des résidences vérifiées partout en Côte d’Ivoire</li>
    <li><i class="fa-solid fa-calendar-check"></i> Réservations et factures au même endroit</li>
    <li><i class="fa-solid fa-headset"></i> Une équipe disponible pendant votre séjour</li>
@endsection

@section('form')
    @include('auth.partials.steps', ['current' => 2])

    <div class="auth-heading">
        <span class="auth-badge"><i class="fa-solid fa-suitcase-rolling"></i> Compte client</span>
        <h2>Vos informations</h2>
        <p>Quelques informations pour créer votre compte.</p>
    </div>

    @include('auth.partials.alerts')

    @include('auth.partials.social', ['verb' => 'S’inscrire', 'divider' => 'ou créez votre compte avec votre email'])

    @include('auth.partials.register-form', ['action' => route('register.client.store')])

    <p class="auth-switch">
        Vous avez déjà un compte ?
        <a href="{{ route('login') }}">Se connecter</a>
    </p>
@endsection
