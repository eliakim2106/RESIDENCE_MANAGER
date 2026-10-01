<?php

namespace App\Http\Controllers;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\NewContactMessage;
use App\Rules\PhoneNumberRule;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/**
 * Formulaire de contact du site (accueil et page Contact) : message enregistré et signalé aux administrateurs.
 */
class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // Champ invisible rempli uniquement par les robots : on fait comme si le message était parti
        if (filled($request->input('site_web'))) {
            return back()->with('contact_sent', true)->withFragment('contact');
        }

        $request->merge([
            'indicatif_telephone' => $request->input('indicatif_telephone') ?: PhoneNumber::defaultDial(),
            'telephone' => filled($request->input('telephone'))
                ? PhoneNumber::normalize((string) $request->input('indicatif_telephone'), (string) $request->input('telephone'))
                : null,
        ]);

        $validated = $request->validateWithBag('contact', [
            'nom' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:191'],
            'indicatif_telephone' => ['required', Rule::in(array_keys(config('phone.countries')))],
            'telephone' => ['nullable', new PhoneNumberRule],
            'sujet' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ], [
            'nom.required' => 'Indiquez votre nom.',
            'email.required' => 'Indiquez votre adresse email pour que nous puissions vous répondre.',
            'email.email' => 'Cette adresse email n’est pas valide.',
            'sujet.required' => 'Précisez le sujet de votre message.',
            'message.required' => 'Écrivez votre message.',
            'message.min' => 'Votre message est un peu court : donnez-nous quelques détails.',
        ]);

        $message = ContactMessage::create([
            'name' => trim($validated['nom']),
            'email' => mb_strtolower(trim($validated['email'])),
            'phone' => $validated['telephone'],
            'indicatif_telephone' => $validated['indicatif_telephone'],
            'subject' => trim($validated['sujet']),
            'message' => trim($validated['message']),
            'statut' => ContactMessageStatus::New,
        ]);

        Notification::send(User::query()->backOffice()->get(), new NewContactMessage($message));

        return back()->with('contact_sent', true)->withFragment('contact');
    }
}
