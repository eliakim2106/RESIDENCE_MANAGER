@extends('layouts.site')

@section('title', 'Détail de la résidence')

@section('content')
    @include('site.partials.navbar')

    <div class="details-page">

        <div class="details-container">

            <div class="details-layout">

                <div class="details-main">

                    @include('site.partials.details.gallery-section')

                    @include('site.partials.details.info-section')

                    @include('site.partials.details.reviews-section')

                    @include('site.partials.details.similar-residences-section')

                </div>

                <div class="details-sidebar">

                    @include('site.partials.details.booking-sidebar-section')

                    @include('site.partials.details.map-section')

                </div>

            </div>

        </div>

    </div>

    {{-- Mobile et tablette : barre de réservation flottante, mène à la carte de réservation --}}
    <div class="booking-bar" id="bookingBar">
        <div class="booking-bar-price">
            <span class="booking-bar-amount">
                <strong>35 000 FCFA</strong>
                <span>/ nuit</span>
            </span>
            <small>
                <i class="fa-solid fa-star"></i>
                4.8 · 124 avis
            </small>
        </div>

        <a href="#reservation" class="booking-bar-btn" data-scroll-to-booking>
            <i class="fa-regular fa-calendar-check"></i>
            <span>Réserver <span class="booking-bar-btn-extra">maintenant</span></span>
        </a>
    </div>

    @include('site.partials.footer')
@endsection
