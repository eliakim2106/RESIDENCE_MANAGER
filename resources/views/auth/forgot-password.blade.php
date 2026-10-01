@extends('layouts.auth')

@section('title', 'Mot de passe oublié')

@section('visual_image', 'assets/images/home/slide-1.webp')
@section('visual_kicker', 'Accès à votre compte')
@section('visual_title')
    Un nouveau mot de passe en <span>2 minutes</span>
@endsection
@section('visual_text', 'Indiquez l’adresse email de votre compte : nous vous envoyons un lien sécurisé pour choisir un nouveau mot de passe.')
@section('visual_points')
    <li><i class="fa-solid fa-envelope-open-text"></i> Un lien personnel, envoyé par email</li>
    <li><i class="fa-solid fa-clock"></i> Valable {{ config('auth.passwords.users.expire') }} minutes, utilisable une seule fois</li>
    <li><i class="fa-solid fa-shield-halved"></i> Votre mot de passe actuel reste valable tant que vous n’en choisissez pas un nouveau</li>
@endsection

@section('topbar_action')
    <a href="{{ route('login') }}" class="auth-topbar-link">Se connecter</a>
@endsection

@section('form')
    <div class="auth-heading">
        <span class="auth-heading-icon"><i class="fa-solid fa-key"></i></span>
        <h2>Mot de passe oublié ?</h2>
        <p>Pas d’inquiétude : saisissez votre adresse email, nous vous envoyons un lien pour en choisir un nouveau.</p>
    </div>

    @include('auth.partials.alerts')

    @if (session('success'))
        <div class="auth-next-steps">
            <p><i class="fa-solid fa-inbox"></i> Pensez à vérifier vos courriers indésirables si l’email n’arrive pas d’ici quelques minutes.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="auth-form" novalidate>
        @csrf

        <div class="auth-field">
            <label for="email">Adresse email</label>
            <div class="auth-input @error('email') is-invalid @enderror">
                <i class="fa-regular fa-envelope"></i>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                    placeholder="vous@exemple.com" autocomplete="email" autofocus required>
            </div>
            @error('email')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="auth-submit">
            Envoyer le lien
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </form>

    <p class="auth-switch">
        Vous vous en souvenez ?
        <a href="{{ route('login') }}">Retour à la connexion</a>
    </p>
@endsection
