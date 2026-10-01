{{-- Connexion avec Google / Facebook (comptes clients). Rien n'est affiché tant qu'aucun fournisseur n'est configuré. --}}
@php $providers = \App\Http\Controllers\Auth\SocialLoginController::enabled(); @endphp

@if ($providers !== [])
    <div class="auth-social">
        @foreach ($providers as $provider => [$label, $icon])
            <a href="{{ route('social.redirect', $provider) }}" class="auth-social-btn is-{{ $provider }}">
                <i class="fa-brands {{ $icon }}"></i>
                {{ $verb ?? 'Continuer' }} avec {{ $label }}
            </a>
        @endforeach
    </div>

    <p class="auth-divider"><span>{{ $divider ?? 'ou avec votre email' }}</span></p>
@endif
