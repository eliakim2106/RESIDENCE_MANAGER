<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon/ds_holding_favicon.png') }}">

    <title>
        @hasSection('title')
            @yield('title') ·
        @endif{{ config('app.name') }}
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
