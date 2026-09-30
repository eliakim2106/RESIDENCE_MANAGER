<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon/ds_holding_favicon.png') }}">

    <title>
        @hasSection('title')
            @yield('title') ·
        @endif DS HOLDING - Dashboard
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @stack('styles')
</head>

<body>
    <div class="admin-layout">

        @include('admin.partials.sidebar')

        <div class="main-content">

            @include('admin.partials.navbar')

            <div class="dashboard-wrapper">
                @yield('content')
            </div>

            <footer class="dashboard-footer">
                <span>© {{ now()->year }} DS HOLDING. Tous droits réservés.</span>
                <span>Version 1.0.0</span>
            </footer>

        </div>

    </div>

    @include('admin.partials.delete-modal')

    @stack('scripts')
</body>

</html>
