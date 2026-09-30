@extends('layouts.site')

@section('title', 'Résidences')

@section('content')
    @include('site.partials.navbar')

    @include('site.partials.residences.residence-hero-section')

    @include('site.partials.residences.residence-filter-section')

    @include('site.partials.residences.residence-grid-section')

    @include('site.partials.residences.residence-cta-section')

    @include('site.partials.footer')
@endsection
