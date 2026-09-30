@extends('layouts.site')

@section('title', 'Accueil')

@section('content')

    @include('site.partials.navbar')

    <main class="home">

        @include('site.partials.home.hero-section')

        @include('site.partials.home.stats-section')

        @include('site.partials.home.residence-populaire-section')

        @include('site.partials.home.why-section')

        @include('site.partials.home.steps-section')

        @include('site.partials.home.testimonials-section')

        @include('site.partials.home.cta-section')

        @include('site.partials.home.contact-section')

    </main>

    @include('site.partials.footer')

@endsection
