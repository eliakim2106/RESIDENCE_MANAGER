{{-- Liste courte de connexions. Paramètre : $logins (collection de LoginLog) --}}
@if ($logins->isEmpty())
    <p class="resa-note">Aucune connexion enregistrée.</p>
@else
    <ul class="login-list">
        @foreach ($logins as $login)
            <li class="{{ $login->successful ? 'is-ok' : 'is-failed' }}">
                <span class="login-icon"><i class="fa-solid {{ $login->deviceIcon() }}"></i></span>
                <span class="resa-unit-text">
                    <strong>{{ $login->device() }}</strong>
                    <small>{{ $login->ip_address ?? 'IP inconnue' }} · {{ $login->created_at?->format('d/m/Y à H:i') }}</small>
                </span>
                @if ($login->successful)
                    <span class="status-pill status-good">Réussie</span>
                @else
                    <span class="status-pill status-critical">Échouée</span>
                @endif
            </li>
        @endforeach
    </ul>
@endif
