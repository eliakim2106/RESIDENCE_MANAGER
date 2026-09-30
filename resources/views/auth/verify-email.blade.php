@extends('layouts.auth')

@section('title', 'Confirmez votre adresse email')

@section('visual_image', 'assets/images/home/slide-1.webp')
@section('visual_kicker', 'Dernière étape')
@section('visual_title')
    Plus qu’un <span>clic</span>
@endsection
@section('visual_text', 'Confirmer votre adresse email protège votre compte et garantit que vous recevrez vos confirmations de réservation.')
@section('visual_points')
    <li><i class="fa-solid fa-envelope-open-text"></i> Ouvrez l’email envoyé par DS HOLDING</li>
    <li><i class="fa-solid fa-hand-pointer"></i> Cliquez sur « Confirmer mon adresse email »</li>
    <li><i class="fa-solid fa-circle-check"></i> Votre espace est aussitôt activé</li>
@endsection

@section('topbar_action')
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="auth-topbar-button">Se déconnecter</button>
    </form>
@endsection

@section('form')
    @include('auth.partials.steps', ['current' => 3])

    <div class="auth-verify">
        <span class="auth-verify-icon"><i class="fa-regular fa-envelope"></i></span>

        <div class="auth-heading">
            <h2>Vérifiez votre boîte mail</h2>
            <p>
                Nous avons envoyé un lien de confirmation à
                <strong>{{ auth()->user()->email }}</strong>.
                Cliquez dessus pour activer votre compte.
            </p>
        </div>
    </div>

    @include('auth.partials.alerts')

    <ul class="auth-tips">
        <li><i class="fa-solid fa-clock"></i> Le lien est valable 60 minutes.</li>
        <li><i class="fa-solid fa-filter"></i> Rien reçu ? Pensez à vérifier vos courriers indésirables.</li>
    </ul>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="auth-submit">
            <i class="fa-solid fa-paper-plane"></i>
            Renvoyer le lien de confirmation
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="auth-switch">
        @csrf
        Mauvaise adresse ?
        <button type="submit" class="auth-link-button">Créer un compte avec une autre adresse</button>
    </form>
@endsection
