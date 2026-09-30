@extends('layouts.auth')

@section('title', 'Inscription')

@section('visual_image', 'assets/images/home/slide-2.webp')
@section('visual_kicker', 'Inscription gratuite')
@section('visual_title')
    Rejoignez <span>DS HOLDING</span>
@endsection
@section('visual_text',
    'Réservez des résidences sélectionnées ou publiez vos établissements sur une plateforme pensée
    pour la Côte d’Ivoire.')
@section('visual_points')
    <li><i class="fa-solid fa-circle-check"></i> Création du compte en moins de 2 minutes</li>
    <li><i class="fa-solid fa-circle-check"></i> Accès immédiat après confirmation de l’email</li>
    <li><i class="fa-solid fa-circle-check"></i> Assistance disponible 24h/24</li>
@endsection

@section('form')
    @include('auth.partials.steps', ['current' => 1])

    <div class="auth-heading">
        <h2>Créer un compte</h2>
        <p>Choisissez le profil qui vous correspond.</p>
    </div>

    <div class="auth-profiles">
        <a href="{{ route('register.client') }}" class="auth-profile">
            <span class="auth-profile-icon"><i class="fa-solid fa-suitcase-rolling"></i></span>
            <span class="auth-profile-body">
                <strong>Je cherche un logement</strong>
                <span>Compte client : réservez des résidences et suivez vos séjours.</span>
            </span>
            <i class="fa-solid fa-chevron-right auth-profile-arrow"></i>
        </a>

        <a href="{{ route('register.owner') }}" class="auth-profile">
            <span class="auth-profile-icon auth-profile-icon-gold"><i class="fa-solid fa-building-user"></i></span>
            <span class="auth-profile-body">
                <strong>Je propose des logements</strong>
                <span>Compte propriétaire : publiez vos établissements et recevez des réservations.</span>
            </span>
            <i class="fa-solid fa-chevron-right auth-profile-arrow"></i>
        </a>
    </div>

    <p class="auth-switch">
        Vous avez déjà un compte ?
        <a href="{{ route('login') }}">Se connecter</a>
    </p>
@endsection
