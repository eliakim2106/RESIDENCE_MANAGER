<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Rules\PhoneNumberRule;
use App\Support\PhoneNumber;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Paramètres du site (super administrateur) : identité, coordonnées, réseaux sociaux, règles de réservation.
 */
class SiteSettingController extends Controller
{
    public function edit(SiteSettings $settings): View
    {
        return view('admin.parametres.edit', [
            'settings' => $settings,
            // Dernière modification de l'onglet Général : date et auteur
            'lastChange' => Setting::query()->where('group', SiteSettings::GROUP)->with('updater')->latest('updated_at')->first(),
        ]);
    }

    public function update(Request $request, SiteSettings $settings): RedirectResponse
    {
        $countries = array_keys(config('phone.countries'));

        // Numéros enregistrés sans espaces ni indicatif, comme partout ailleurs
        foreach (['contact_phone' => 'contact_phone_dial', 'contact_whatsapp' => 'contact_whatsapp_dial'] as $number => $dial) {
            $request->merge([
                $dial => $request->input($dial) ?: PhoneNumber::defaultDial(),
                $number => filled($request->input($number)) ? PhoneNumber::normalize((string) $request->input($dial), (string) $request->input($number)) : null,
            ]);
        }

        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:60'],
            'site_description' => ['required', 'string', 'max:180'],
            'site_about' => ['required', 'string', 'max:300'],
            'contact_address' => ['required', 'string', 'max:150'],
            'contact_email' => ['required', 'email', 'max:191'],
            'contact_phone_dial' => ['required', Rule::in($countries)],
            'contact_phone' => ['nullable', new PhoneNumberRule('contact_phone_dial')],
            'contact_whatsapp_dial' => ['required', Rule::in($countries)],
            'contact_whatsapp' => ['nullable', new PhoneNumberRule('contact_whatsapp_dial')],
            'contact_hours' => ['nullable', 'string', 'max:120'],
            ...array_fill_keys(array_keys(SiteSettings::SOCIALS), ['nullable', 'url:http,https', 'max:255']),
            'booking_request_ttl_hours' => ['required', 'integer', 'between:1,336'],
            'booking_service_fee_rate' => ['required', 'numeric', 'between:0,30'],
            'booking_max_nights' => ['required', 'integer', 'between:1,365'],
            'booking_max_days_ahead' => ['required', 'integer', 'between:7,730'],
        ], [
            'site_description.max' => 'La description ne doit pas dépasser :max caractères (les moteurs de recherche coupent au-delà).',
            '*.url' => 'Indiquez l’adresse complète de la page, commençant par https://',
            'booking_request_ttl_hours.between' => 'Le délai de réponse doit être compris entre 1 heure et 14 jours (336 heures).',
            'booking_service_fee_rate.between' => 'Les frais de service doivent être compris entre 0 et 30 %.',
        ], [
            'site_name' => 'nom du site',
            'site_description' => 'description',
            'site_about' => 'présentation',
            'contact_address' => 'adresse',
            'contact_email' => 'email de contact',
            'contact_phone' => 'téléphone',
            'contact_whatsapp' => 'numéro WhatsApp',
            'contact_hours' => 'horaires',
            'booking_request_ttl_hours' => 'délai de réponse',
            'booking_service_fee_rate' => 'frais de service',
            'booking_max_nights' => 'durée maximale',
            'booking_max_days_ahead' => 'anticipation maximale',
        ]);

        $settings->save(array_map(fn ($value) => is_string($value) ? trim($value) : $value, $validated));

        return redirect()->route('admin.parametres.edit')->with('success', 'Les paramètres du site sont enregistrés.');
    }
}
