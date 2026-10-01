<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="no-js">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a1f44">
    <meta name="description" content="@yield('description', $site->get('site_description'))">

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
