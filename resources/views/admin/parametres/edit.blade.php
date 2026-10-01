@extends('layouts.admin')

@php
    use App\Support\SiteSettings;

    $value = fn (string $key) => old($key, $settings->get($key));
    $bookingValue = fn (string $key) => old(SiteSettings::BOOKING[$key], $settings->booking($key));
    $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'dsholding.ci';
    $sections = [
        'identite' => ['fa-id-card', 'Identité', ['site_name', 'site_description', 'site_about']],
        'coordonnees' => ['fa-address-book', 'Coordonnées', ['contact_address', 'contact_email', 'contact_phone', 'contact_phone_dial', 'contact_whatsapp', 'contact_whatsapp_dial', 'contact_hours']],
        'reseaux' => ['fa-share-nodes', 'Réseaux sociaux', array_keys(SiteSettings::SOCIALS)],
        'reservation' => ['fa-calendar-check', 'Réservation en ligne', array_values(SiteSettings::BOOKING)],
    ];
    $sectionHasError = fn (array $fields): bool => collect($fields)->contains(fn ($field) => $errors->has($field));
@endphp

@section('title', 'Paramètres du site')

@section('content')
    @include('admin.parametres.partials.header', ['lastChange' => $lastChange, 'previewUrl' => route('home')])

    @include('partials.flash')

    @if ($errors->any())
        <div class="prm-alert" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>
                <strong>{{ $errors->count() > 1 ? $errors->count().' champs sont à corriger' : 'Un champ est à corriger' }}</strong>
                <span>Rien n’a été enregistré. Les sections concernées sont signalées.</span>
            </div>
        </div>
    @endif

    {{-- Raccourcis vers les sections --}}
    <nav class="prm-jump" aria-label="Sections">
        @foreach ($sections as $id => [$icon, $label, $fields])
            <a href="#{{ $id }}" class="{{ $sectionHasError($fields) ? 'has-error' : '' }}">
                <i class="fa-solid {{ $icon }}"></i>
                {{ $label }}
                @if ($sectionHasError($fields))
                    <i class="fa-solid fa-circle-exclamation prm-jump-error" aria-label="contient une erreur"></i>
                @endif
            </a>
        @endforeach
    </nav>

    <form method="POST" action="{{ route('admin.parametres.update') }}" id="settingsForm" class="admin-form prm-layout" novalidate data-dirty-form>
        @csrf
        @method('PUT')

        <div class="prm-main">

            {{-- ========== Identité ========== --}}
            <section class="prm-card" id="identite">
                <header class="prm-card-head">
                    <span class="prm-card-icon tone-blue"><i class="fa-solid fa-id-card"></i></span>
                    <div>
                        <h2>Identité</h2>
                        <p>Nom affiché dans les onglets du navigateur et le pied de page, description lue par les moteurs de recherche.</p>
                    </div>
                </header>

                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label for="site_name">Nom du site <span class="required">*</span></label>
                        <input type="text" name="site_name" id="site_name" value="{{ $value('site_name') }}" maxlength="60" required>
                        @error('site_name') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group form-group-full">
                        <label for="site_description">Description pour les moteurs de recherche <span class="required">*</span></label>
                        <textarea name="site_description" id="site_description" rows="2" maxlength="180" required data-char-count data-char-ideal="160">{{ $value('site_description') }}</textarea>
                        <p class="field-help prm-help-row"><span>Affichée sous le lien du site dans Google : 150 à 160 caractères conseillés.</span> <span data-char-counter></span></p>
                        @error('site_description') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group form-group-full">
                        <label for="site_about">Présentation du pied de page <span class="required">*</span></label>
                        <textarea name="site_about" id="site_about" rows="3" maxlength="300" required data-char-count>{{ $value('site_about') }}</textarea>
                        <p class="field-help prm-help-row"><span>Une ou deux phrases sous le logo, en bas de chaque page.</span> <span data-char-counter></span></p>
                        @error('site_about') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- ========== Coordonnées ========== --}}
            <section class="prm-card" id="coordonnees">
                <header class="prm-card-head">
                    <span class="prm-card-icon tone-gold"><i class="fa-solid fa-address-book"></i></span>
                    <div>
                        <h2>Coordonnées</h2>
                        <p>Pied de page, menu mobile, page Contact, pages d’erreur et pages légales. Un champ facultatif laissé vide n’est pas affiché.</p>
                    </div>
                </header>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="contact_address">Adresse <span class="required">*</span></label>
                        <div class="settings-input-icon">
                            <i class="fa-solid fa-location-dot"></i>
                            <input type="text" name="contact_address" id="contact_address" value="{{ $value('contact_address') }}" maxlength="150" required placeholder="Cocody, Abidjan">
                        </div>
                        @error('contact_address') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="contact_email">Email de contact <span class="required">*</span></label>
                        <div class="settings-input-icon">
                            <i class="fa-regular fa-envelope"></i>
                            <input type="email" name="contact_email" id="contact_email" value="{{ $value('contact_email') }}" maxlength="191" required>
                        </div>
                        @error('contact_email') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="contact_phone">Téléphone <small>(facultatif)</small></label>
                        @include('partials.phone-field', ['phoneId' => 'contact_phone', 'phoneName' => 'contact_phone', 'phoneDialName' => 'contact_phone_dial', 'phoneValue' => $settings->get('contact_phone'), 'phoneDial' => $settings->get('contact_phone_dial')])
                        @error('contact_phone') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="contact_whatsapp">WhatsApp <small>(facultatif)</small></label>
                        @include('partials.phone-field', ['phoneId' => 'contact_whatsapp', 'phoneName' => 'contact_whatsapp', 'phoneDialName' => 'contact_whatsapp_dial', 'phoneValue' => $settings->get('contact_whatsapp'), 'phoneDial' => $settings->get('contact_whatsapp_dial')])
                        @error('contact_whatsapp') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group form-group-full">
                        <label for="contact_hours">Horaires <small>(facultatif)</small></label>
                        <div class="settings-input-icon">
                            <i class="fa-regular fa-clock"></i>
                            <input type="text" name="contact_hours" id="contact_hours" value="{{ $value('contact_hours') }}" maxlength="120" placeholder="Du lundi au samedi, de 8 h à 19 h">
                        </div>
                        @error('contact_hours') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- ========== Réseaux sociaux ========== --}}
            <section class="prm-card" id="reseaux">
                <header class="prm-card-head">
                    <span class="prm-card-icon tone-violet"><i class="fa-solid fa-share-nodes"></i></span>
                    <div>
                        <h2>Réseaux sociaux</h2>
                        <p>Une icône apparaît dans le pied de page pour chaque réseau renseigné. Collez l’adresse complète de la page.</p>
                    </div>
                </header>

                <div class="prm-socials">
                    @foreach (SiteSettings::SOCIALS as $key => [$label, $icon])
                        <div class="form-group">
                            <label for="{{ $key }}" class="visually-hidden">{{ $label }}</label>
                            <div class="prm-social {{ $errors->has($key) ? 'is-invalid' : '' }}">
                                <span class="prm-social-brand brand-{{ Str::after($key, 'social_') }}"><i class="fa-brands {{ $icon }}"></i></span>
                                <span class="prm-social-name">{{ $label }}</span>
                                <input type="url" name="{{ $key }}" id="{{ $key }}" value="{{ $value($key) }}" maxlength="255" placeholder="https://…" data-social-input>
                                <a href="{{ $value($key) ?: '#' }}" target="_blank" rel="noopener" class="prm-social-open" title="Ouvrir la page" aria-label="Ouvrir la page {{ $label }}" data-social-open @if (! $value($key)) hidden @endif>
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            </div>
                            @error($key) <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ========== Réservation ========== --}}
            <section class="prm-card" id="reservation">
                <header class="prm-card-head">
                    <span class="prm-card-icon tone-green"><i class="fa-solid fa-calendar-check"></i></span>
                    <div>
                        <h2>Réservation en ligne</h2>
                        <p>Règles appliquées aux réservations faites sur le site. Elles remplacent les valeurs du fichier .env.</p>
                    </div>
                </header>

                <div class="prm-rules">
                    @foreach ([
                        ['request_ttl_hours', 'Délai de réponse', 'fa-hourglass-half', 'heures', 1, 336, 1, 'Sans réponse de l’établissement dans ce délai, la demande expire ; un paiement déjà effectué est remboursé.'],
                        ['service_fee_rate', 'Frais de service', 'fa-percent', '%', 0, 30, 0.5, 'Ajoutés au prix de l’hébergement et affichés au client. 0 : aucun frais.'],
                        ['max_nights', 'Durée maximale', 'fa-moon', 'nuits', 1, 365, 1, 'Au-delà, le client est invité à contacter l’établissement.'],
                        ['max_days_ahead', 'Anticipation maximale', 'fa-calendar-days', 'jours', 7, 730, 1, 'Délai maximal entre aujourd’hui et la date d’arrivée.'],
                    ] as [$key, $label, $icon, $unit, $min, $max, $step, $help])
                        @php $name = SiteSettings::BOOKING[$key]; @endphp
                        <div class="prm-rule {{ $errors->has($name) ? 'is-invalid' : '' }}">
                            <div class="prm-rule-head">
                                <span class="prm-rule-icon"><i class="fa-solid {{ $icon }}"></i></span>
                                <label for="{{ $name }}">{{ $label }} <span class="required">*</span></label>
                            </div>
                            <div class="prm-stepper">
                                <button type="button" data-step="-{{ $step }}" aria-label="Diminuer"><i class="fa-solid fa-minus"></i></button>
                                <input type="number" name="{{ $name }}" id="{{ $name }}" value="{{ $bookingValue($key) }}" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}" required>
                                <span class="prm-stepper-unit">{{ $unit }}</span>
                                <button type="button" data-step="{{ $step }}" aria-label="Augmenter"><i class="fa-solid fa-plus"></i></button>
                            </div>
                            <p class="field-help">{{ $help }}</p>
                            @error($name) <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- ========== Aperçus en direct ========== --}}
        <aside class="prm-aside" aria-label="Aperçus">
            <section class="prm-preview">
                <h3><i class="fa-brands fa-google"></i> Aperçu dans Google</h3>
                <div class="prm-google">
                    <div class="prm-google-site">
                        <img src="{{ asset('assets/images/favicon/ds_holding_favicon.png') }}" alt="">
                        <div>
                            <strong data-preview="site_name">{{ $value('site_name') }}</strong>
                            <small>https://{{ $host }}</small>
                        </div>
                    </div>
                    <p class="prm-google-title">Accueil · <span data-preview="site_name">{{ $value('site_name') }}</span></p>
                    <p class="prm-google-text" data-preview="site_description" data-preview-limit="160">{{ Str::limit($value('site_description'), 160) }}</p>
                </div>
            </section>

            <section class="prm-preview">
                <h3><i class="fa-solid fa-table-columns"></i> Pied de page</h3>
                <div class="prm-footer">
                    <strong data-preview="site_name">{{ $value('site_name') }}</strong>
                    <p data-preview="site_about">{{ $value('site_about') }}</p>
                    <ul>
                        <li><i class="fa-solid fa-location-dot"></i> <span data-preview="contact_address">{{ $value('contact_address') }}</span></li>
                        <li data-preview-row="contact_phone" @unless ($settings->phone()) hidden @endunless><i class="fa-solid fa-phone"></i> <span data-preview-phone="contact_phone">{{ $settings->phone() }}</span></li>
                        <li><i class="fa-solid fa-envelope"></i> <span data-preview="contact_email">{{ $value('contact_email') }}</span></li>
                        <li data-preview-row="contact_hours" @unless ($value('contact_hours')) hidden @endunless><i class="fa-regular fa-clock"></i> <span data-preview="contact_hours">{{ $value('contact_hours') }}</span></li>
                    </ul>
                    <div class="prm-footer-socials">
                        @foreach (SiteSettings::SOCIALS as $key => [$label, $icon])
                            <span data-preview-row="{{ $key }}" title="{{ $label }}" @unless ($value($key)) hidden @endunless><i class="fa-brands {{ $icon }}"></i></span>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="prm-preview">
                <h3><i class="fa-solid fa-scale-balanced"></i> Règles appliquées</h3>
                <ul class="prm-summary">
                    <li><i class="fa-solid fa-hourglass-half"></i> <span>Une demande expire après <strong data-preview="booking_request_ttl_hours">{{ $bookingValue('request_ttl_hours') }}</strong> h sans réponse.</span></li>
                    <li><i class="fa-solid fa-percent"></i> <span>Frais de service : <strong data-preview="booking_service_fee_rate">{{ $bookingValue('service_fee_rate') }}</strong> %.</span></li>
                    <li><i class="fa-solid fa-moon"></i> <span>Séjours de <strong data-preview="booking_max_nights">{{ $bookingValue('max_nights') }}</strong> nuits au plus.</span></li>
                    <li><i class="fa-solid fa-calendar-days"></i> <span>Réservations jusqu’à <strong data-preview="booking_max_days_ahead">{{ $bookingValue('max_days_ahead') }}</strong> jours à l’avance.</span></li>
                </ul>
            </section>
        </aside>
    </form>

    @include('admin.parametres.partials.savebar', ['form' => 'settingsForm', 'label' => 'Enregistrer les paramètres'])
@endsection

@push('scripts')
    @vite('resources/js/admin/parametres.js')
@endpush
