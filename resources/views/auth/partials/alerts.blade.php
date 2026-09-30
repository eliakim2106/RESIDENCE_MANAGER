{{-- Messages de la session. Les erreurs de validation s'affichent aussi sous chaque champ. --}}
@if (session('success'))
    <div class="auth-alert auth-alert-success" role="status">
        <i class="fa-solid fa-circle-check"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="auth-alert auth-alert-error" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="auth-alert auth-alert-error" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span>
            {{ $errors->count() > 1 ? 'Veuillez corriger les '.$errors->count().' erreurs ci-dessous.' : $errors->first() }}
        </span>
    </div>
@endif
