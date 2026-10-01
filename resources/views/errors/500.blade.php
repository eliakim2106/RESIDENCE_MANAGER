@extends('errors.layout', ['icon' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4 2.6-2.6z"/>'])

@section('code', '500')
@section('title', 'Une erreur est survenue')
@section('message', 'Un problème technique nous empêche d’afficher cette page. Notre équipe en est informée ; réessayez dans quelques instants.')

@section('actions')
    <button type="button" class="error-btn error-btn-gold" onclick="location.reload()">Réessayer</button>
    <a href="{{ url('/') }}" class="error-btn">Retour à l’accueil</a>
@endsection
