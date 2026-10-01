@extends('errors.layout', ['icon' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M8.5 11h5"/>'])

@section('code', '404')
@section('title', 'Page introuvable')
@section('message', 'Cette page n’existe pas ou a été déplacée. Une résidence que vous cherchiez n’est peut-être plus en ligne.')

@section('actions')
    <a href="{{ Route::has('residences.index') ? route('residences.index') : url('/') }}" class="error-btn error-btn-gold">Voir les résidences</a>
    <a href="{{ url('/') }}" class="error-btn">Retour à l’accueil</a>
@endsection
