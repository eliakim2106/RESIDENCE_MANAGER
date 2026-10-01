@extends('errors.layout', ['icon' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4 2.6-2.6z"/>'])

@section('code', '503')
@section('title', 'Maintenance en cours')
@section('message', 'Nous améliorons le site. Il sera de nouveau disponible dans quelques instants : merci de votre patience.')

@section('actions')
    <button type="button" class="error-btn error-btn-gold" onclick="location.reload()">Actualiser</button>
@endsection

@section('no_help', true)
