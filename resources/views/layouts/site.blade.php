<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="no-js">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a1f44">
    <meta name="description" content="@yield('description', $site->get('site_description'))">

    {{--
        Aperçu d'un lien partagé (WhatsApp, Facebook, Messenger, X…) : titre, description et photo.
        Une page peut fournir og_title et og_image ; sinon titre de la page et visuel du site.
        Les sections sont déjà échappées par Blade : seules les valeurs par défaut passent par e().
    --}}
    @php
        $shareTitle = trim($__env->yieldContent('og_title')) ?: (trim($__env->yieldContent('title')) ? trim($__env->yieldContent('title')).' · '.e($site->name()) : e($site->name()));
        $shareDescription = trim($__env->yieldContent('description')) ?: e($site->get('site_description'));
        $shareImage = url(html_entity_decode(trim($__env->yieldContent('og_image'))) ?: asset('assets/images/home/slide-1.webp'));
    @endphp
    <meta property="og:site_name" content="{{ $site->name() }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{!! $shareTitle !!}">
    <meta property="og:description" content="{!! $shareDescription !!}">
    <meta property="og:image" content="{{ $shareImage }}">
    <meta property="og:image:alt" content="{!! $shareTitle !!}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{!! $shareTitle !!}">
    <meta name="twitter:description" content="{!! $shareDescription !!}">
    <meta name="twitter:image" content="{{ $shareImage }}">

    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon/ds_holding_favicon.png') }}">

    <title>
        @hasSection('title')
            @yield('title') ·
        @endif{{ $site->name() }}
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    @vite(['resources/css/site.css', 'resources/js/site.js'])
    @stack('styles')
</head>

<body>
    @yield('content')

    @stack('scripts')
</body>

</html>
