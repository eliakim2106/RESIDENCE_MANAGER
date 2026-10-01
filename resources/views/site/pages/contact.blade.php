@extends('site.pages.layout')

@section('title', 'Contact')
@section('description', 'Contactez DS HOLDING : une question sur une résidence, une réservation ou un partenariat ? Écrivez-nous.')
@section('page_kicker', 'Contact')
@section('page_title', 'Parlons de votre projet')
@section('page_lead', 'Une question sur une résidence, une réservation ou un partenariat ? Écrivez-nous.')

@section('page')
    <div class="home info-contact">
        @include('site.partials.home.contact-section')

        <div class="container">
            <div class="info-cta">
                <div>
                    <strong>Une question courante ?</strong>
                    <span>La réponse se trouve peut-être déjà dans nos questions fréquentes.</span>
                </div>
                <a href="{{ route('pages.faq') }}" class="site-btn site-btn-ghost-dark">Questions fréquentes <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
@endsection
