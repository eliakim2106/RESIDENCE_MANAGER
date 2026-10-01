{{-- Mise en page des pages d'information : bandeau titre puis contenu. Sections : page_kicker, page_title, page_lead, page --}}
@extends('layouts.site')

@section('content')
    @include('site.partials.navbar')

    <main class="info-page">
        <header class="info-hero">
            <div class="container">
                <nav class="rd-breadcrumb info-breadcrumb" aria-label="Fil d’Ariane">
                    <a href="{{ route('home') }}">Accueil</a>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>@yield('title')</span>
                </nav>
                @hasSection('page_kicker')
                    <span class="info-kicker">@yield('page_kicker')</span>
                @endif
                <h1>@yield('page_title')</h1>
                @hasSection('page_lead')
                    <p>@yield('page_lead')</p>
                @endif
            </div>
        </header>

        <div class="info-body">
            @yield('page')
        </div>
    </main>

    @include('site.partials.footer')
@endsection
