@extends('layouts.auth')

@section('title', 'Créer un compte propriétaire')

@section('visual_image', 'assets/images/home/slide-2.webp')
@section('visual_kicker', 'Compte propriétaire')
@section('visual_title')
    Faites grandir <span>votre activité</span>
@endsection
@section('visual_text', 'Publiez vos établissements sur DS HOLDING et gérez vos réservations depuis un espace professionnel.')
@section('visual_points')
    <li><i class="fa-solid fa-building"></i> Publiez vos établissements et leurs unités</li>
    <li><i class="fa-solid fa-sliders"></i> Gérez tarifs, photos et disponibilités</li>
    <li><i class="fa-solid fa-chart-line"></i> Suivez vos réservations et vos revenus</li>
@endsection

@section('form')
    @include('auth.partials.steps', ['current' => 2])

    <div class="auth-heading">
        <span class="auth-badge auth-badge-gold"><i class="fa-solid fa-building-user"></i> Compte propriétaire</span>
        <h2>Vos informations</h2>
        <p>Vous pourrez ajouter vos établissements juste après la confirmation de votre email.</p>
    </div>

    @include('auth.partials.alerts')

    @include('auth.partials.register-form', ['action' => route('register.owner.store')])

    <p class="auth-switch">
        Vous avez déjà un compte ?
        <a href="{{ route('login') }}">Se connecter</a>
    </p>
@endsection
