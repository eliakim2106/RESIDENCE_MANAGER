@extends('layouts.site')

@section('title', 'Accueil')

@section('content')

    @include('site.partials.navbar')

    <main class="home">

        @include('site.partials.home.hero-section')

        {{-- Sections masquables depuis Paramètres du site > Contenu des pages > Accueil --}}
        @if ($site->visible('home_stats'))
            @include('site.partials.home.stats-section')
        @endif

        @if ($site->visible('home_featured'))
            @include('site.partials.home.residence-populaire-section')
        @endif

        @if ($site->visible('home_why'))
            @include('site.partials.home.why-section')
        @endif

        @if ($site->visible('home_steps'))
            @include('site.partials.home.steps-section')
        @endif

        @if ($site->visible('home_reviews'))
            @include('site.partials.home.testimonials-section')
        @endif

        @if ($site->visible('home_cta'))
            @include('site.partials.home.cta-section')
        @endif

        @if ($site->visible('home_contact'))
            @include('site.partials.home.contact-section')
        @endif

    </main>

    @include('site.partials.footer')

@endsection
