@extends('layouts.auth')

@section('title', 'Finaliser mon compte')

@section('visual_image', 'assets/images/home/slide-1.webp')
@section('visual_kicker', 'Bienvenue')
@section('visual_title')
    Encore <span>une étape</span>
@endsection
@section('visual_text', 'Votre compte est créé. Ces informations permettent aux établissements de vous joindre et de préparer votre arrivée.')
@section('visual_points')
    <li><i class="fa-solid fa-phone"></i> Un numéro pour être contacté avant votre séjour</li>
    <li><i class="fa-solid fa-location-dot"></i> Votre ville et votre pays de résidence</li>
    <li><i class="fa-solid fa-lock"></i> Vos données ne sont partagées qu’avec l’établissement réservé</li>
@endsection

@section('topbar_action')
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="auth-topbar-button">Se déconnecter</button>
    </form>
@endsection

@section('form')
    <div class="auth-heading">
        <img src="{{ $user->avatarUrl() }}" alt="" class="auth-heading-avatar">
        <h2>Bonjour {{ Str::before($user->name, ' ') }} !</h2>
        <p>Complétez votre profil pour accéder à votre espace et réserver. <strong>{{ $user->email }}</strong></p>
    </div>

    @include('auth.partials.alerts')

    <form method="POST" action="{{ route('client.profile.complete.store') }}" class="auth-form" novalidate>
        @csrf
        @method('PUT')

        <div class="auth-field">
            <label for="phoneNumber">Téléphone</label>
            @include('partials.phone-field', ['phoneVariant' => 'auth', 'phoneId' => 'phoneNumber', 'phoneValue' => $user->phone, 'phoneDial' => $user->indicatif_telephone, 'phoneRequired' => true])
            @error('telephone') <p class="auth-error">{{ $message }}</p> @enderror
        </div>

        <div class="auth-row">
            <div class="auth-field">
                <label for="pays">Pays</label>
                <div class="auth-input @error('pays') is-invalid @enderror">
                    <i class="fa-solid fa-earth-africa"></i>
                    <select id="pays" name="pays" required>
                        @foreach ($countries as $country)
                            <option value="{{ $country }}" @selected(old('pays', $user->country ?: 'Côte d’Ivoire') === $country)>{{ $country }}</option>
                        @endforeach
                    </select>
                </div>
                @error('pays') <p class="auth-error">{{ $message }}</p> @enderror
            </div>

            <div class="auth-field">
                <label for="ville">Ville</label>
                <div class="auth-input @error('ville') is-invalid @enderror">
                    <i class="fa-solid fa-city"></i>
                    <input type="text" id="ville" name="ville" value="{{ old('ville', $user->city) }}" placeholder="Abidjan" autocomplete="address-level2" required>
                </div>
                @error('ville') <p class="auth-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <button type="submit" class="auth-submit">
            Accéder à mon espace
            <i class="fa-solid fa-arrow-right"></i>
        </button>
    </form>
@endsection
