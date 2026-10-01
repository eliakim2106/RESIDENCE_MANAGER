@extends('errors.layout', ['icon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'])

@section('code', '419')
@section('title', 'Page expirée')
@section('message', 'Votre session a expiré, par sécurité, après un moment d’inactivité. Rechargez la page puis renvoyez le formulaire.')

@section('actions')
    <button type="button" class="error-btn error-btn-gold" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')">Revenir au formulaire</button>
    <a href="{{ url('/') }}" class="error-btn">Retour à l’accueil</a>
@endsection
