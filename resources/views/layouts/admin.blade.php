<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a1f44">

    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon/ds_holding_favicon.png') }}">

    <title>
        @hasSection('title')
            @yield('title') ·
        @endif DS HOLDING Administration
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @stack('styles')
</head>

<body class="admin-body">
    <div class="admin-layout">

        @include('admin.partials.sidebar')

        <div class="sidebar-backdrop" data-sidebar-close></div>

        <div class="main-content">

            @include('admin.partials.navbar')

            <main class="dashboard-wrapper">
                @yield('content')
            </main>

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
