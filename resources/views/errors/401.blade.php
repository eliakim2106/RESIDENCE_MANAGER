@extends('errors.layout', ['icon' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>'])

@section('code', '401')
@section('title', 'Connexion requise')
@section('message', 'Vous devez être connecté pour accéder à cette page.')

@section('actions')
    <a href="{{ Route::has('login') ? route('login') : url('/') }}" class="error-btn error-btn-gold">Se connecter</a>
    <a href="{{ url('/') }}" class="error-btn">Retour à l’accueil</a>
@endsection
