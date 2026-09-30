@extends('layouts.auth')

@section('title', 'Connexion')

@section('visual_image', 'assets/images/home/slide-1.webp')
@section('visual_kicker', 'Espace membre')
@section('visual_title')
    Heureux de vous <span>revoir</span>
@endsection
@section('visual_text',
    'Connectez-vous pour retrouver vos réservations, vos établissements et toutes vos informations
    en un seul endroit.')
@section('visual_points')
    <li>
        <i class="fa-solid fa-calendar-check"></i> Suivez vos réservations en temps réel
    </li>
    <li>
        <i class="fa-solid fa-building">
        </i> Gérez vos établissements et vos unités
    </li>
    <li>
        <i class="fa-solid fa-shield-halved">
        </i> Paiements et données sécurisés
    </li>
@endsection

@section('form')
    <div class="auth-heading">
        <h2>Connexion</h2>
        <p>Accédez à votre espace DS HOLDING.</p>
    </div>

    @include('auth.partials.alerts')

    <form method="POST" action="{{ route('login.store') }}" class="auth-form" novalidate>
        @csrf

        <div class="auth-field">
            <label for="email">Adresse email</label>
            <div class="auth-input @error('email') is-invalid @enderror">
                <i class="fa-regular fa-envelope"></i>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                    placeholder="vous@exemple.com" autocomplete="email" autofocus required>
            </div>
        </div>

        <div class="auth-field">
            <div class="auth-label-row">
                <label for="password">Mot de passe</label>
                <a href="{{ route('home') }}#contact" class="auth-link-small">Mot de passe oublié ?</a>
            </div>
            <div class="auth-input @error('email') is-invalid @enderror">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Votre mot de passe"
                    autocomplete="current-password" required>
                <button type="button" class="auth-toggle-password" data-target="password"
                    aria-label="Afficher le mot de passe">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>
        </div>

        <label class="auth-check">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span class="auth-check-box"><i class="fa-solid fa-check"></i></span>
            <span>Se souvenir de moi</span>
        </label>

        <button type="submit" class="auth-submit">
            Se connecter
            <i class="fa-solid fa-arrow-right"></i>
        </button>
    </form>

    <p class="auth-switch">
        Nouveau sur DS HOLDING ?
        <a href="{{ route('register') }}">Créer un compte</a>
    </p>
@endsection
