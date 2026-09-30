{{-- Formulaire d'inscription commun au client et au propriétaire. Paramètres : $action (URL d'envoi) --}}
@php
    use App\Http\Requests\Auth\RegisterRequest;

    $phoneFormats = [
        '+225' => ['🇨🇮', 10, '07 01 23 45 67'],
        '+221' => ['🇸🇳', 9, '77 123 45 67'],
        '+223' => ['🇲🇱', 8, '65 12 34 56'],
        '+226' => ['🇧🇫', 8, '70 12 34 56'],
        '+228' => ['🇹🇬', 8, '90 12 34 56'],
        '+229' => ['🇧🇯', 8, '97 12 34 56'],
        '+233' => ['🇬🇭', 9, '24 123 4567'],
        '+224' => ['🇬🇳', 9, '620 123 456'],
        '+33' => ['🇫🇷', 9, '6 12 34 56 78'],
        '+32' => ['🇧🇪', 9, '470 12 34 56'],
        '+1' => ['🇨🇦', 10, '(514) 123-4567'],
    ];
    $countries = ["Côte d'Ivoire", 'Sénégal', 'Mali', 'Burkina Faso', 'Togo', 'Bénin', 'Ghana', 'Guinée', 'France', 'Belgique', 'Canada'];
    $countryCode = old('country_code', '+225');
    $country = old('pays', "Côte d'Ivoire");
@endphp

<form method="POST" action="{{ $action }}" class="auth-form" novalidate>
    @csrf

    <div class="auth-row">
        <div class="auth-field">
            <label for="nom">Nom</label>
            <div class="auth-input @error('nom') is-invalid @enderror">
                <i class="fa-regular fa-user"></i>
                <input type="text" id="nom" name="nom" value="{{ old('nom') }}" placeholder="Kouassi" autocomplete="family-name" required>
            </div>
            @error('nom') <p class="auth-error">{{ $message }}</p> @enderror
        </div>

        <div class="auth-field">
            <label for="prenoms">Prénoms</label>
            <div class="auth-input @error('prenoms') is-invalid @enderror">
                <i class="fa-regular fa-user"></i>
                <input type="text" id="prenoms" name="prenoms" value="{{ old('prenoms') }}" placeholder="Aya Marie" autocomplete="given-name" required>
            </div>
            @error('prenoms') <p class="auth-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="auth-field">
        <label for="email">Adresse email</label>
        <div class="auth-input @error('email') is-invalid @enderror">
            <i class="fa-regular fa-envelope"></i>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="vous@exemple.com" autocomplete="email" required>
        </div>
        @error('email')
            <p class="auth-error">{{ $message }}</p>
        @else
            <p class="auth-help">Un lien de confirmation vous sera envoyé à cette adresse.</p>
        @enderror
    </div>

    <div class="auth-field">
        <label for="phoneNumber">Téléphone</label>
        <div class="auth-input auth-input-phone @error('telephone') is-invalid @enderror @error('country_code') is-invalid @enderror">
            <select name="country_code" id="countryCode" aria-label="Indicatif du pays">
                @foreach ($phoneFormats as $code => [$flag, $length, $placeholder])
                    @continue(! in_array($code, RegisterRequest::COUNTRY_CODES, true))
                    <option value="{{ $code }}" data-length="{{ $length }}" data-placeholder="{{ $placeholder }}" @selected($countryCode === $code)>
                        {{ $flag }} {{ $code }}
                    </option>
                @endforeach
            </select>
            <input type="tel" id="phoneNumber" name="telephone" value="{{ old('telephone') }}" autocomplete="tel-national" inputmode="tel" required>
        </div>
        @error('telephone')
            <p class="auth-error">{{ $message }}</p>
        @else
            <p class="auth-help" id="phoneHelp"></p>
        @enderror
    </div>

    <div class="auth-row">
        <div class="auth-field">
            <label for="pays">Pays</label>
            <div class="auth-input @error('pays') is-invalid @enderror">
                <i class="fa-solid fa-earth-africa"></i>
                <select id="pays" name="pays" autocomplete="country-name" required>
                    @foreach ($countries as $name)
                        <option value="{{ $name }}" @selected($country === $name)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @error('pays') <p class="auth-error">{{ $message }}</p> @enderror
        </div>

        <div class="auth-field">
            <label for="ville">Ville</label>
            <div class="auth-input @error('ville') is-invalid @enderror">
                <i class="fa-solid fa-location-dot"></i>
                <input type="text" id="ville" name="ville" value="{{ old('ville') }}" placeholder="Abidjan" autocomplete="address-level2" required>
            </div>
            @error('ville') <p class="auth-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="auth-row">
        <div class="auth-field">
            <label for="password">Mot de passe</label>
            <div class="auth-input @error('password') is-invalid @enderror">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="8 caractères minimum" autocomplete="new-password" required data-password-strength>
                <button type="button" class="auth-toggle-password" data-target="password" aria-label="Afficher le mot de passe">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>
            <div class="auth-strength" data-strength-meter aria-live="polite">
                <span class="auth-strength-bar"><span></span><span></span><span></span><span></span></span>
                <span class="auth-strength-label">Lettres et chiffres, 8 caractères minimum</span>
            </div>
            @error('password') <p class="auth-error">{{ $message }}</p> @enderror
        </div>

        <div class="auth-field">
            <label for="confirmPassword">Confirmation</label>
            <div class="auth-input @error('confirm_password') is-invalid @enderror">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="confirmPassword" name="confirm_password" placeholder="Retapez le mot de passe" autocomplete="new-password" required>
                <button type="button" class="auth-toggle-password" data-target="confirmPassword" aria-label="Afficher le mot de passe">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>
            <p class="auth-match" id="passwordMatch" aria-live="polite"></p>
            @error('confirm_password') <p class="auth-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <label class="auth-check @error('terms') is-invalid @enderror">
        <input type="checkbox" name="terms" value="1" @checked(old('terms')) required>
        <span class="auth-check-box"><i class="fa-solid fa-check"></i></span>
        <span>J’accepte les conditions générales d’utilisation et la politique de confidentialité.</span>
    </label>
    @error('terms') <p class="auth-error">{{ $message }}</p> @enderror

    <div class="auth-actions">
        <a href="{{ route('register') }}" class="auth-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            Retour
        </a>
        <button type="submit" class="auth-submit">
            Créer mon compte
            <i class="fa-solid fa-arrow-right"></i>
        </button>
    </div>
</form>
