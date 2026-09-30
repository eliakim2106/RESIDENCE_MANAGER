@if (session('success'))
    <div class="alert success-alert">
        <div class="success-icon">
            <i class="fa-solid fa-check"></i>
        </div>

        <div class="success-content">
            <h4>Succès !</h4>

            <button type="button" class="alert-close">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <p>{{ session('success') }}</p>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        <div class="alert-icon">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>

        <div class="alert-content">
            <div class="alert-header">
                <h4>Erreur !</h4>

                <button type="button" class="alert-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <p>{{ session('error') }}</p>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-validation">
        <div class="alert-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <div class="alert-content">
            <div class="alert-header">
                <h4>Veuillez corriger les erreurs suivantes</h4>

                <button type="button" class="alert-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <ul class="alert-list">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
