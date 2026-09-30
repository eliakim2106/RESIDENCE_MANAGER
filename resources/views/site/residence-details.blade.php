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

    @include('site.partials.footer')
@endsection
