@extends('layouts.site')

@section('title', 'Inscription client')

@section('content')

    <div class="auth-container">

        <!-- BANNIÈRE -->

        <div class="auth-banner">

            <div class="overlay"></div>

            <div class="banner-content">

                <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" alt="DS Holding">

                <h1>

                    Votre séjour commence ici

                </h1>

                <p>

                    Créez votre espace client pour suivre vos réservations et retrouver facilement votre historique de
                    séjour.

                </p>

            </div>

        </div>

        <!-- FORMULAIRE -->

        <div class="auth-form-side">

            <div class="auth-card register-card">

                <img src="{{ asset('assets/images/logo/ds_holding_logo.png') }}" class="form-logo">

                <span class="register-badge">

                    ÉTAPE 2 SUR 2

                </span>

                <h2>

                    Informations personnelles

                </h2>

                <p>

                    Complétez vos informations.

                </p>

                @include('auth.partials.alerts')

                <form method="POST" action="{{ route('register.client') }}">

                    @csrf

                    <div class="form-row">

                        <div class="input-group">

                            <label>Nom</label>

                            <input type="text" name="nom" required value="{{ old('nom') }}">

                        </div>

                        <div class="input-group">

                            <label>Prénoms</label>

                            <input type="text" name="prenoms" required value="{{ old('prenoms') }}">

                        </div>

                    </div>

                    <div class="phone">

                        <label>Numéro de téléphone</label>

                        <div class="phone-wrapper">

                            <select name="country_code" id="countryCode" class="country-select">

                                <option value="+225" @selected(old('country_code', '+225') === '+225') data-length="10"
                                    data-placeholder="07 01 23 45 67">
                                    🇨🇮 +225
                                </option>

                                <option value="+221" @selected(old('country_code', '+225') === '+221') data-length="9"
                                    data-placeholder="77 123 45 67">
                                    🇸🇳 +221
                                </option>

                                <option value="+223" @selected(old('country_code', '+225') === '+223') data-length="8"
                                    data-placeholder="65 12 34 56">
                                    🇲🇱 +223
                                </option>

                                <option value="+226" @selected(old('country_code', '+225') === '+226') data-length="8"
                                    data-placeholder="70 12 34 56">
                                    🇧🇫 +226
                                </option>

                                <option value="+228" @selected(old('country_code', '+225') === '+228') data-length="8"
                                    data-placeholder="90 12 34 56">
                                    🇹🇬 +228
                                </option>

                                <option value="+229" @selected(old('country_code', '+225') === '+229') data-length="8"
                                    data-placeholder="97 12 34 56">
                                    🇧🇯 +229
                                </option>

                                <option value="+233" @selected(old('country_code', '+225') === '+233') data-length="9"
                                    data-placeholder="24 123 4567">
                                    🇬🇭 +233
                                </option>

                                <option value="+224" @selected(old('country_code', '+225') === '+224') data-length="9"
                                    data-placeholder="620 123 456">
                                    🇬🇳 +224
                                </option>

                                <option value="+33" @selected(old('country_code', '+225') === '+33') data-length="9"
                                    data-placeholder="6 12 34 56 78">
                                    🇫🇷 +33
                                </option>

                                <option value="+32" @selected(old('country_code', '+225') === '+32') data-length="9"
                                    data-placeholder="470 12 34 56">
                                    🇧🇪 +32
                                </option>

                                <option value="+1" @selected(old('country_code', '+225') === '+1') data-length="10"
                                    data-placeholder="(514) 123-4567">
                                    🇨🇦 +1
                                </option>

                            </select>

                            <input type="tel" id="phoneNumber" name="telephone" class="phone-number"
                                value="{{ old('telephone') }}" required>

                        </div>

                        <small id="phoneHelp" class="phone-help">
                        </small>

                    </div>

                    <div class="input-group">

                        <label>Email</label>

                        <input type="email" name="email" required value="{{ old('email') }}">

                    </div>

                    <div class="form-row">

                        <div class="input-group">

                            <label>Pays</label>

                            <select name="pays" class="country-select" required>

                                <option value="">
                                    Sélectionner un pays
                                </option>

                                <option value="Côte d'Ivoire" @selected(old('pays') === "Côte d'Ivoire")>
                                    🇨🇮 Côte d'Ivoire
                                </option>

                                <option value="Sénégal" @selected(old('pays') === 'Sénégal')>
                                    🇸🇳 Sénégal
                                </option>

                                <option value="Mali" @selected(old('pays') === 'Mali')>
                                    🇲🇱 Mali
                                </option>

                                <option value="Burkina Faso" @selected(old('pays') === 'Burkina Faso')>
                                    🇧🇫 Burkina Faso
                                </option>

                                <option value="France" @selected(old('pays') === 'France')>
                                    🇫🇷 France
                                </option>

                                <option value="Belgique" @selected(old('pays') === 'Belgique')>
                                    🇧🇪 Belgique
                                </option>

                                <option value="Canada" @selected(old('pays') === 'Canada')>
                                    🇨🇦 Canada
                                </option>

                            </select>

                        </div>

                        <div class="input-group">

                            <label>Ville</label>

                            <input type="text" name="ville" placeholder="Ex : Abidjan" required
                                value="{{ old('ville') }}">

                        </div>

                    </div>

                    <div class="form-row">

                        <div class="input-group">

                            <label>Mot de passe</label>

                            <div class="password-wrapper">

                                <input type="password" id="password" name="password" required>

                                <button type="button" class="toggle-password" data-target="password">

                                    <i class="fas fa-eye"></i>

                                </button>

                            </div>

                        </div>

                        <div class="input-group">

                            <label>Confirmer le mot de passe</label>

                            <div class="password-wrapper">

                                <input type="password" id="confirmPassword" name="confirm_password" required>

                                <button type="button" class="toggle-password" data-target="confirmPassword">

                                    <i class="fas fa-eye"></i>

                                </button>

                            </div>

                        </div>

                        <small id="passwordMatch" class="password-match">
                        </small>

                    </div>

                    <label class="checkbox-row">

                        <input type="checkbox" name="terms" value="1" @checked(old('terms')) required>

                        J'accepte les conditions
                        générales d'utilisation.

                    </label>

                    <div class="form-actions">

                        <a href="{{ route('register') }}" class="btn-back">

                            Retour

                        </a>

                        <button type="submit" class="btn-auth">

                            Créer mon compte

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection
