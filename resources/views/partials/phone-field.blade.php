{{--
    Champ téléphone avec indicatif du pays (drapeau, recherche, longueur et format selon le pays).
    Utilisé par tous les formulaires qui demandent un téléphone.

    Paramètres :
      $phoneId        identifiant du champ numéro (défaut : telephone)
      $phoneName      nom du champ numéro (défaut : telephone)
      $phoneDialName  nom du champ indicatif (défaut : indicatif_telephone) ; à changer pour deux téléphones dans un même formulaire
      $phoneValue     numéro national enregistré (sans indicatif)
      $phoneDial      indicatif enregistré (ex. « +225 ») ; défaut : config('phone.default')
      $phoneRequired  champ obligatoire (défaut : false)
      $phoneVariant   apparence : admin (défaut), auth (inscription), etab (formulaire d'établissement)
      $phoneInvalid   affiche le champ en erreur (défaut : erreur de validation sur le numéro ou l'indicatif)

    Le select natif de l'indicatif reste le champ envoyé : sans JavaScript, il fonctionne tel quel.
--}}
@php
    use App\Support\PhoneNumber;

    // Paramètres préfixés « phone » : une variable $name, $value… de la vue appelante (boucle @foreach) ne s'y substitue pas
    $id = $phoneId ?? 'telephone';
    $name = $phoneName ?? 'telephone';
    $dialName = $phoneDialName ?? 'indicatif_telephone';
    $variant = $phoneVariant ?? 'admin';
    $required = $phoneRequired ?? false;
    $dial = $phoneDial ?? null;
    $value = $phoneValue ?? null;
    $countries = PhoneNumber::countries();
    $preferred = config('phone.preferred', []);
    $selectedDial = old($dialName, $dial ?? null) ?: PhoneNumber::defaultDial();
    $selectedDial = isset($countries[$selectedDial]) ? $selectedDial : PhoneNumber::defaultDial();
    $selected = $countries[$selectedDial];
    $number = old($name, $value ?? '');
    $invalid = $phoneInvalid ?? ($errors->has($name) || $errors->has($dialName));
@endphp

<div class="phone-field phone-field--{{ $variant }} {{ $invalid ? 'is-invalid' : '' }}" data-phone-field>
    <span class="phone-field-dial">
        <span class="fi fi-{{ $selected['iso'] }} phone-flag" aria-hidden="true" data-phone-native-flag></span>
        <select name="{{ $dialName }}" class="phone-field-native" aria-label="Indicatif du pays" data-phone-dial>
            @foreach ($countries as $code => $country)
                <option value="{{ $code }}"
                    data-iso="{{ $country['iso'] }}"
                    data-name="{{ $country['name'] }}"
                    data-lengths="{{ implode(',', $country['lengths']) }}"
                    data-groups="{{ implode(',', $country['groups']) }}"
                    data-example="{{ $country['example'] }}"
                    data-trunk="{{ $country['trunk'] ? '1' : '0' }}"
                    data-preferred="{{ in_array($code, $preferred, true) ? '1' : '0' }}"
                    @selected($code === $selectedDial)>{{ $country['name'] }} ({{ $code }})</option>
            @endforeach
        </select>
    </span>

    <input type="tel"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $number }}"
        class="phone-field-input"
        inputmode="tel"
        autocomplete="tel-national"
        placeholder="{{ $selected['example'] }}"
        maxlength="20"
        @if ($required) required @endif
        @if ($invalid) aria-invalid="true" @endif
        aria-describedby="{{ $id }}-hint"
        data-phone-number>
</div>
<small class="phone-field-hint" id="{{ $id }}-hint" data-phone-hint aria-live="polite">
    {{ implode(' ou ', $selected['lengths']) }} chiffres · ex. {{ $selected['example'] }}
</small>
