@extends('site.pages.layout')

@section('title', 'Désinscription')
@section('page_kicker', 'Newsletter')
@section('page_title', 'Vous êtes désinscrit')
@section('page_lead', 'L’adresse '.$email.' ne recevra plus notre newsletter.')

@section('page')
    <div class="container">
        <div class="info-cta">
            <div>
                <strong>Vous changez d’avis ?</strong>
                <span>Vous pouvez vous réinscrire à tout moment depuis le bas de chaque page.</span>
            </div>
            <a href="{{ route('home') }}" class="site-btn site-btn-gold">Retour à l’accueil <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
@endsection
