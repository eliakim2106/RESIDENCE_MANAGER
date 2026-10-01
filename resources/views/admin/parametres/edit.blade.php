@extends('layouts.admin')

@php
    use App\Support\SiteSettings;

    $value = fn (string $key) => old($key, $settings->get($key));
    $bookingValue = fn (string $key) => old(SiteSettings::BOOKING[$key], $settings->booking($key));
    $sections = [
        'identite' => ['fa-id-card', 'Identité'],
        'coordonnees' => ['fa-address-book', 'Coordonnées'],
        'reseaux' => ['fa-share-nodes', 'Réseaux sociaux'],
        'reservation' => ['fa-calendar-check', 'Réservation en ligne'],
    ];
    // Section contenant une erreur : signalée dans le sommaire
    $fields = [
        'identite' => ['site_name', 'site_description', 'site_about'],
        'coordonnees' => ['contact_address', 'contact_email', 'contact_phone', 'contact_phone_dial', 'contact_whatsapp', 'contact_whatsapp_dial', 'contact_hours'],
        'reseaux' => array_keys(SiteSettings::SOCIALS),
        'reservation' => array_values(SiteSettings::BOOKING),
    ];
@endphp

@section('title', 'Paramètres du site')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-sliders"></i></span>
            <div>
                <h1>Paramètres du site</h1>
                <p>Nom, coordonnées, réseaux sociaux et règles de réservation affichés sur le site public.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn-secondary">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                Voir le site
            </a>
        </div>
    </div>

    @include('partials.flash')

    @if ($errors->any())
        <div class="settings-alert" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            {{ $errors->count() > 1 ? $errors->count().' champs sont à corriger.' : 'Un champ est à corriger.' }} Rien n’a été enregistré.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.parametres.update') }}" class="admin-form settings-layout" novalidate>
        @csrf
        @method('PUT')

        {{-- ========== Sommaire ========== --}}
        <aside class="settings-nav">
            <nav aria-label="Sections">
                @foreach ($sections as $id => [$icon, $label])
                    @php $hasError = collect($fields[$id])->contains(fn ($field) => $errors->has($field)); @endphp
                    <a href="#{{ $id }}" class="{{ $hasError ? 'has-error' : '' }}">
                        <i class="fa-solid {{ $icon }}"></i>
                        {{ $label }}
                        @if ($hasError)
                            <span class="settings-nav-error" aria-label="contient une erreur"><i class="fa-solid fa-circle-exclamation"></i></span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <button type="submit" class="btn-primary settings-save">
                <i class="fa-solid fa-floppy-disk"></i>
                Enregistrer
            </button>
            <p class="field-help">Les changements s’appliquent immédiatement sur tout le site.</p>
        </aside>

        <div class="settings-main">

            {{-- ========== Identité ========== --}}
            <section class="form-section" id="identite">
                <div class="form-section-header">
                    <h2>Identité</h2>
                    <p>Nom affiché dans les titres d’onglet et le pied de page, description lue par les moteurs de recherche.</p>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="site_name">Nom du site <span class="required">*</span></label>
                        <input type="text" name="site_name" id="site_name" value="{{ $value('site_name') }}" maxlength="60" required>
                        @error('site_name') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group form-group-full">
                        <label for="site_description">Description <span class="required">*</span></label>
                        <textarea name="site_description" id="site_description" rows="2" maxlength="180" required data-char-count>{{ $value('site_description') }}</textarea>
                        <p class="field-help">Résumé affiché sous le lien du site dans les résultats Google : 150 à 160 caractères conseillés. <span data-char-counter></span></p>
                        @error('site_description') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group form-group-full">
                        <label for="site_about">Présentation du pied de page <span class="required">*</span></label>
                        <textarea name="site_about" id="site_about" rows="3" maxlength="300" required data-char-count>{{ $value('site_about') }}</textarea>
                        <p class="field-help">Une ou deux phrases sous le logo, en bas de chaque page. <span data-char-counter></span></p>
                        @error('site_about') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- ========== Coordonnées ========== --}}
            <section class="form-section" id="coordonnees">
                <div class="form-section-header">
                    <h2>Coordonnées</h2>
                    <p>Affichées dans le pied de page, le menu mobile, la page Contact et les pages d’erreur. Un champ facultatif laissé vide n’est pas affiché.</p>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="contact_address">Adresse <span class="required">*</span></label>
                        <input type="text" name="contact_address" id="contact_address" value="{{ $value('contact_address') }}" maxlength="150" required placeholder="Cocody, Abidjan">
                        @error('contact_address') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="contact_email">Email de contact <span class="required">*</span></label>
                        <input type="email" name="contact_email" id="contact_email" value="{{ $value('contact_email') }}" maxlength="191" required>
                        @error('contact_email') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="contact_phone">Téléphone</label>
                        @include('partials.phone-field', ['phoneId' => 'contact_phone', 'phoneName' => 'contact_phone', 'phoneDialName' => 'contact_phone_dial', 'phoneValue' => $settings->get('contact_phone'), 'phoneDial' => $settings->get('contact_phone_dial')])
                        @error('contact_phone') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="contact_whatsapp">WhatsApp</label>
                        @include('partials.phone-field', ['phoneId' => 'contact_whatsapp', 'phoneName' => 'contact_whatsapp', 'phoneDialName' => 'contact_whatsapp_dial', 'phoneValue' => $settings->get('contact_whatsapp'), 'phoneDial' => $settings->get('contact_whatsapp_dial')])
                        @error('contact_whatsapp') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group form-group-full">
                        <label for="contact_hours">Horaires <small>(facultatif)</small></label>
                        <input type="text" name="contact_hours" id="contact_hours" value="{{ $value('contact_hours') }}" maxlength="120" placeholder="Du lundi au samedi, de 8 h à 19 h">
                        @error('contact_hours') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- ========== Réseaux sociaux ========== --}}
            <section class="form-section" id="reseaux">
                <div class="form-section-header">
                    <h2>Réseaux sociaux</h2>
                    <p>Une icône apparaît dans le pied de page pour chaque réseau renseigné.</p>
                </div>

                <div class="form-grid">
                    @foreach (SiteSettings::SOCIALS as $key => [$label, $icon])
                        <div class="form-group">
                            <label for="{{ $key }}">{{ $label }}</label>
                            <div class="settings-input-icon">
                                <i class="fa-brands {{ $icon }}"></i>
                                <input type="url" name="{{ $key }}" id="{{ $key }}" value="{{ $value($key) }}" maxlength="255" placeholder="https://…">
                            </div>
                            @error($key) <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ========== Réservation ========== --}}
            <section class="form-section" id="reservation">
                <div class="form-section-header">
                    <h2>Réservation en ligne</h2>
                    <p>Règles appliquées aux réservations faites sur le site. Elles remplacent les valeurs du fichier .env.</p>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="booking_request_ttl_hours">Délai de réponse de l’établissement <span class="required">*</span></label>
                        <div class="settings-input-unit">
                            <input type="number" name="booking_request_ttl_hours" id="booking_request_ttl_hours" value="{{ $bookingValue('request_ttl_hours') }}" min="1" max="336" step="1" required>
                            <span>heures</span>
                        </div>
                        <p class="field-help">Sans réponse dans ce délai, la demande expire et un paiement déjà effectué est remboursé.</p>
                        @error('booking_request_ttl_hours') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="booking_service_fee_rate">Frais de service <span class="required">*</span></label>
                        <div class="settings-input-unit">
                            <input type="number" name="booking_service_fee_rate" id="booking_service_fee_rate" value="{{ $bookingValue('service_fee_rate') }}" min="0" max="30" step="0.5" required>
                            <span>%</span>
                        </div>
                        <p class="field-help">Ajoutés au prix de l’hébergement et affichés au client. 0 : aucun frais.</p>
                        @error('booking_service_fee_rate') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="booking_max_nights">Durée maximale d’un séjour <span class="required">*</span></label>
                        <div class="settings-input-unit">
                            <input type="number" name="booking_max_nights" id="booking_max_nights" value="{{ $bookingValue('max_nights') }}" min="1" max="365" step="1" required>
                            <span>nuits</span>
                        </div>
                        <p class="field-help">Au-delà, le client est invité à contacter l’établissement.</p>
                        @error('booking_max_nights') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="booking_max_days_ahead">Réservation possible jusqu’à <span class="required">*</span></label>
                        <div class="settings-input-unit">
                            <input type="number" name="booking_max_days_ahead" id="booking_max_days_ahead" value="{{ $bookingValue('max_days_ahead') }}" min="7" max="730" step="1" required>
                            <span>jours à l’avance</span>
                        </div>
                        @error('booking_max_days_ahead') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        </div>
    </form>
@endsection
