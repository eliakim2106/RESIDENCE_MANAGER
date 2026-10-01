@extends('layouts.auth')

@section('title', 'Nouveau mot de passe')

@section('visual_image', 'assets/images/home/slide-1.webp')
@section('visual_kicker', 'Accès à votre compte')
@section('visual_title')
    Choisissez un <span>nouveau mot de passe</span>
@endsection
@section('visual_text', 'Un mot de passe solide protège vos réservations, vos paiements et vos informations personnelles.')
@section('visual_points')
    <li><i class="fa-solid fa-check"></i> 8 caractères au minimum</li>
    <li><i class="fa-solid fa-check"></i> Mélangez lettres, chiffres et symboles</li>
    <li><i class="fa-solid fa-check"></i> N’utilisez pas le même que sur d’autres sites</li>
@endsection

@section('topbar_action')
    <a href="{{ route('login') }}" class="auth-topbar-link">Se connecter</a>
@endsection

@section('form')
    <div class="auth-heading">
        <span class="auth-heading-icon"><i class="fa-solid fa-lock"></i></span>
        <h2>Nouveau mot de passe</h2>
        <p>Dernière étape : choisissez le mot de passe que vous utiliserez désormais.</p>
    </div>

    @include('auth.partials.alerts')

    <form method="POST" action="{{ route('password.update') }}" class="auth-form" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="auth-field">
            <label for="email">Adresse email</label>
            <div class="auth-input @error('email') is-invalid @enderror">
                <i class="fa-regular fa-envelope"></i>
                <input type="email" id="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required @if ($email !== '') readonly @endif>
            </div>
            @error('email')
                <p class="auth-error">
                    {{ $message }}
                    @if (str_contains($message, 'plus valable'))
                        <a href="{{ route('password.request') }}">Demander un nouveau lien</a>
                    @endif
                </p>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password">Nouveau mot de passe</label>
            <div class="auth-input @error('password') is-invalid @enderror">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="8 caractères minimum" autocomplete="new-password" autofocus required>
                <button type="button" class="auth-toggle-password" data-target="password" aria-label="Afficher le mot de passe">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>
            @error('password')
                <p class="auth-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="auth-field">
            <label for="password_confirmation">Confirmer le mot de passe</label>
            <div class="auth-input">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Saisissez-le de nouveau" autocomplete="new-password" required>
                <button type="button" class="auth-toggle-password" data-target="password_confirmation" aria-label="Afficher le mot de passe">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="auth-submit">
            Enregistrer le mot de passe
            <i class="fa-solid fa-arrow-right"></i>
        </button>
    </form>
@endsection
