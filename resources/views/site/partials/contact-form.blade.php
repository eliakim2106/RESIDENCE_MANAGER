{{-- Formulaire de contact (accueil et page Contact). Les erreurs utilisent le sac « contact » pour ne pas se mêler aux autres formulaires. --}}
@php $contactErrors = $errors->getBag('contact'); @endphp

<form method="POST" action="{{ route('contact.store') }}" class="home-contact-form" data-reveal style="--reveal-delay: 120ms" novalidate>
    @csrf
    <h3>Envoyez-nous un message</h3>

    @if (session('contact_sent'))
        <div class="contact-sent" role="status">
            <i class="fa-solid fa-circle-check"></i>
            <span><strong>Message envoyé.</strong> Merci, notre équipe vous répondra par email dans les meilleurs délais.</span>
        </div>
    @elseif ($contactErrors->any())
        <div class="contact-failed" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>Votre message n’est pas parti : vérifiez les champs signalés.</span>
        </div>
    @endif

    {{-- Piège à robots : champ masqué que les visiteurs ne remplissent pas --}}
    <div class="contact-trap" aria-hidden="true">
        <label for="contact-website">Site web</label>
        <input type="text" id="contact-website" name="site_web" tabindex="-1" autocomplete="off">
    </div>

    <div class="home-form-row">
        <div class="home-field">
            <label for="contact-name">Nom complet</label>
            <input type="text" id="contact-name" name="nom" value="{{ old('nom', auth()->user()?->name) }}" placeholder="Votre nom" autocomplete="name" required class="{{ $contactErrors->has('nom') ? 'is-invalid' : '' }}">
            @if ($contactErrors->has('nom')) <p class="home-field-error">{{ $contactErrors->first('nom') }}</p> @endif
        </div>

        <div class="home-field">
            <label for="contact-email">Email</label>
            <input type="email" id="contact-email" name="email" value="{{ old('email', auth()->user()?->email) }}" placeholder="vous@exemple.com" autocomplete="email" required class="{{ $contactErrors->has('email') ? 'is-invalid' : '' }}">
            @if ($contactErrors->has('email')) <p class="home-field-error">{{ $contactErrors->first('email') }}</p> @endif
        </div>
    </div>

    <div class="home-field">
        <label for="contact-phone">Téléphone <small>(facultatif)</small></label>
        @include('partials.phone-field', ['phoneVariant' => 'auth', 'phoneId' => 'contact-phone', 'phoneValue' => auth()->user()?->phone, 'phoneDial' => auth()->user()?->indicatif_telephone, 'phoneInvalid' => $contactErrors->has('telephone')])
        @if ($contactErrors->has('telephone')) <p class="home-field-error">{{ $contactErrors->first('telephone') }}</p> @endif
    </div>

    <div class="home-field">
        <label for="contact-subject">Sujet</label>
        <input type="text" id="contact-subject" name="sujet" value="{{ old('sujet', request('sujet')) }}" placeholder="Réservation, information, partenariat…" maxlength="150" required class="{{ $contactErrors->has('sujet') ? 'is-invalid' : '' }}">
        @if ($contactErrors->has('sujet')) <p class="home-field-error">{{ $contactErrors->first('sujet') }}</p> @endif
    </div>

    <div class="home-field">
        <label for="contact-message">Message</label>
        <textarea id="contact-message" name="message" rows="5" placeholder="Votre message" maxlength="3000" required class="{{ $contactErrors->has('message') ? 'is-invalid' : '' }}">{{ old('message') }}</textarea>
        @if ($contactErrors->has('message')) <p class="home-field-error">{{ $contactErrors->first('message') }}</p> @endif
    </div>

    <button type="submit" class="site-btn site-btn-gold home-btn-lg">
        Envoyer le message
        <i class="fa-solid fa-paper-plane"></i>
    </button>
</form>
