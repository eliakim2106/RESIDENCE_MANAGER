{{--
    Pages de connexion et d'inscription.
    Sections attendues : visual_image, visual_kicker, visual_title, visual_text, visual_points (liste <li>), topbar_action, form.
--}}
@extends('layouts.site')

@section('content')
    <div class="auth-shell">

        {{-- Visuel : grand écran uniquement --}}
        <aside class="auth-visual" aria-hidden="true">
            <img src="{{ asset(View::yieldContent('visual_image', 'assets/images/home/slide-1.webp')) }}" alt="" class="auth-visual-bg">

            <div class="auth-visual-inner">
                <a href="{{ route('home') }}" class="auth-visual-logo" tabindex="-1">
                    <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" alt="DS HOLDING">
                </a>

                <div class="auth-visual-content">
                    <span class="auth-kicker">@yield('visual_kicker')</span>
                    <h1>@yield('visual_title')</h1>
                    <p>@yield('visual_text')</p>

                    <ul class="auth-points">
                        @yield('visual_points')
                    </ul>
                </div>
            </div>
        </aside>

        {{-- Formulaire --}}
        <main class="auth-main">
            <header class="auth-topbar">
                <a href="{{ route('home') }}" class="auth-topbar-logo">
                    <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" alt="DS HOLDING, retour à l’accueil">
                </a>

                <a href="{{ route('home') }}" class="auth-back">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Retour au site</span>
                </a>

                <div class="auth-topbar-action">
                    @yield('topbar_action')
                </div>
            </header>

            <div class="auth-panel">
                @yield('form')
            </div>

            <footer class="auth-foot">
                <span>© {{ now()->year }} DS HOLDING</span>
                <span class="auth-foot-secure"><i class="fa-solid fa-lock"></i> Connexion sécurisée</span>
            </footer>
        </main>

    </div>
@endsection
