{{--
    Pages d'erreur. Autonomes : styles intégrés, sans Vite ni requête en base,
    pour s'afficher même quand l'application est en panne (erreur 500, maintenance).
    Sections : code, title, message ; $icon facultatif (chemin SVG), $actions facultatif.
--}}
@php
    $home = Route::has('home') ? route('home') : url('/');
    $user = rescue(fn () => auth()->user(), null, false);
    $space = rescue(fn () => $user?->homeUrl(), null, false);
@endphp
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0a1f44">
    <title>@yield('code') · @yield('title') · {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon/ds_holding_favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy: #0a1f44;
            --navy-dark: #06142f;
            --gold: #d4a72c;
            --gold-light: #f2c94c;
            --muted: rgba(255, 255, 255, 0.72);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            background:
                radial-gradient(circle at 80% 10%, rgba(212, 167, 44, 0.2), transparent 40%),
                radial-gradient(circle at 10% 90%, rgba(21, 101, 192, 0.25), transparent 45%),
                linear-gradient(160deg, var(--navy) 0%, var(--navy-dark) 100%);
            color: #fff;
            font-family: "Poppins", system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        header {
            padding: 24px 16px;
            text-align: center;
        }

        header img {
            height: 46px;
            filter: brightness(0) invert(1);
        }

        main {
            flex: 1;
            display: grid;
            place-items: center;
            padding: 24px 16px 48px;
        }

        .error-card {
            width: 100%;
            max-width: 560px;
            text-align: center;
        }

        .error-icon {
            display: inline-grid;
            place-items: center;
            width: 88px;
            height: 88px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.06);
            color: var(--gold-light);
        }

        .error-icon svg {
            width: 40px;
            height: 40px;
        }

        .error-code {
            margin: 0;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: clamp(4.5rem, 3rem + 8vw, 7.5rem);
            font-weight: 700;
            line-height: 1;
            letter-spacing: -0.04em;
        }

        h1 {
            margin: 12px 0 10px;
            font-size: clamp(1.4rem, 1.1rem + 1.2vw, 1.9rem);
            font-weight: 600;
        }

        p {
            margin: 0 auto;
            max-width: 460px;
            color: var(--muted);
            line-height: 1.7;
        }

        .error-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 32px;
        }

        .error-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            padding: 0 22px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 999px;
            background: transparent;
            color: #fff;
            font: inherit;
            font-size: 0.92rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.2s, background-color 0.2s, border-color 0.2s;
        }

        .error-btn:hover {
            border-color: #fff;
            background: rgba(255, 255, 255, 0.08);
        }

        .error-btn-gold {
            border-color: transparent;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%);
            color: var(--navy);
        }

        .error-btn-gold:hover {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%);
            transform: translateY(-2px);
        }

        .error-btn:focus-visible {
            outline: 3px solid var(--gold-light);
            outline-offset: 3px;
        }

        .error-help {
            margin-top: 28px;
            font-size: 0.88rem;
        }

        .error-help a {
            color: var(--gold-light);
        }

        footer {
            padding: 20px 16px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.82rem;
            text-align: center;
        }

        @media (max-width: 480px) {
            .error-btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <header>
        <a href="{{ $home }}" aria-label="{{ config('app.name') }}, accueil">
            <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" alt="{{ config('app.name') }}">
        </a>
    </header>

    <main>
        <div class="error-card">
            <span class="error-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    {!! $icon ?? '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/>' !!}
                </svg>
            </span>

            <p class="error-code">@yield('code')</p>
            <h1>@yield('title')</h1>
            <p>@yield('message')</p>

            <div class="error-actions">
                @hasSection('actions')
                    @yield('actions')
                @else
                    <a href="{{ $home }}" class="error-btn error-btn-gold">Retour à l’accueil</a>
                    @if ($space)
                        <a href="{{ $space }}" class="error-btn">Mon espace</a>
                    @else
                        <button type="button" class="error-btn" onclick="history.length > 1 ? history.back() : location.assign('{{ $home }}')">Page précédente</button>
                    @endif
                @endif
            </div>

            @unless (View::hasSection('no_help'))
                <p class="error-help">Le problème persiste ? <a href="mailto:contact@dsholding.ci">contact@dsholding.ci</a></p>
            @endunless
        </div>
    </main>

    <footer>© {{ date('Y') }} DS HOLDING</footer>
</body>

</html>
