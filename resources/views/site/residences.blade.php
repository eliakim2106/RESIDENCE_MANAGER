@extends('layouts.site')

@section('title', 'Nos résidences')

@section('content')
    @include('site.partials.navbar')

    {{-- Un seul formulaire GET : barre de recherche, filtres et tri --}}
    <form method="GET" action="{{ route('residences.index') }}" class="listing" id="listingForm" data-listing-form>

        @include('site.partials.residences.residence-hero-section')

        <div class="container listing-body">
            <div class="listing-layout">

                @include('site.partials.residences.residence-filter-section')

                @include('site.partials.residences.residence-grid-section')

            </div>
        </div>

    </form>

    @if ($site->visible('listing_cta'))
        @include('site.partials.residences.residence-cta-section')
    @endif

    @include('site.partials.footer')
@endsection
