@extends('layouts.account')

@section('title', 'Mon profil')
@section('account_heading', 'Mon profil')
@section('account_subtitle', 'Vos coordonnées sont transmises à l’établissement lors de vos réservations.')

@section('account')
    <div class="acc-grid acc-grid-wide">
        <section class="acc-card">
            <div class="acc-card-head"><h2>Mes informations</h2></div>

            <form method="POST" action="{{ route('client.profile.update') }}" class="acc-form acc-form-grid">
                @csrf
                @method('PUT')

                <label class="acc-field acc-field-wide">
                    <span>Nom complet <span class="acc-required">*</span></span>
                    <input type="text" name="nom" value="{{ old('nom', $user->name) }}" required maxlength="191" autocomplete="name">
                    @error('nom') <p class="acc-error">{{ $message }}</p> @enderror
                </label>

                <label class="acc-field acc-field-wide">
                    <span>Adresse email <span class="acc-required">*</span></span>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="191" autocomplete="email">
                    <small>Si vous la changez, un lien de confirmation sera envoyé à la nouvelle adresse.</small>
                    @error('email') <p class="acc-error">{{ $message }}</p> @enderror
                </label>

                <div class="acc-field acc-field-wide">
                    <span>Téléphone <span class="acc-required">*</span></span>
                    @include('partials.phone-field', ['phoneValue' => $user->phone, 'phoneDial' => $user->indicatif_telephone, 'phoneRequired' => true])
                    @error('telephone') <p class="acc-error">{{ $message }}</p> @enderror
                </div>

                <label class="acc-field">
                    <span>Ville <span class="acc-required">*</span></span>
                    <input type="text" name="ville" value="{{ old('ville', $user->city) }}" required maxlength="100" autocomplete="address-level2">
                    @error('ville') <p class="acc-error">{{ $message }}</p> @enderror
                </label>

                <label class="acc-field">
                    <span>Pays <span class="acc-required">*</span></span>
                    <select name="pays" required>
                        @foreach ($countries as $country)
                            <option value="{{ $country }}" @selected(old('pays', $user->country ?: 'Côte d’Ivoire') === $country)>{{ $country }}</option>
                        @endforeach
                    </select>
                    @error('pays') <p class="acc-error">{{ $message }}</p> @enderror
                </label>

                <button type="submit" class="acc-btn acc-btn-gold">Enregistrer</button>
            </form>
        </section>

        <aside class="acc-col">
            <section class="acc-card">
                <div class="acc-card-head"><h2>Mot de passe</h2></div>

                @if ($user->usesSocialLogin())
                    <p class="acc-small">
                        <i class="fa-brands {{ $user->google_id ? 'fa-google' : 'fa-facebook-f' }}"></i>
                        Vous vous connectez avec {{ $user->google_id ? 'Google' : 'Facebook' }}. Vous pouvez aussi définir un mot de passe pour vous connecter avec votre email.
                    </p>
                @endif

                <form method="POST" action="{{ route('client.profile.password') }}" class="acc-form">
                    @csrf
                    @method('PUT')
                    @unless ($user->usesSocialLogin())
                        <label class="acc-field">
                            <span>Mot de passe actuel</span>
                            <input type="password" name="mot_de_passe_actuel" required autocomplete="current-password">
                            @error('mot_de_passe_actuel') <p class="acc-error">{{ $message }}</p> @enderror
                        </label>
                    @endunless
                    <label class="acc-field">
                        <span>Nouveau mot de passe</span>
                        <input type="password" name="password" required autocomplete="new-password" placeholder="8 caractères minimum">
                        @error('password') <p class="acc-error">{{ $message }}</p> @enderror
                    </label>
                    <label class="acc-field">
                        <span>Confirmation</span>
                        <input type="password" name="password_confirmation" required autocomplete="new-password">
                    </label>
                    <button type="submit" class="acc-btn acc-btn-light">{{ $user->usesSocialLogin() ? 'Définir le mot de passe' : 'Changer le mot de passe' }}</button>
                </form>
            </section>

            <section class="acc-card">
                <div class="acc-card-head"><h2>Mon compte</h2></div>
                <dl class="acc-amounts">
                    <div><dt>Client depuis</dt><dd>{{ $user->created_at->translatedFormat('d F Y') }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $user->hasVerifiedEmail() ? 'Confirmé' : 'À confirmer' }}</dd></div>
                    <div><dt>Connexion</dt><dd>{{ $user->google_id ? 'Google' : ($user->facebook_id ? 'Facebook' : 'Email et mot de passe') }}</dd></div>
                </dl>
            </section>
        </aside>
    </div>
@endsection
