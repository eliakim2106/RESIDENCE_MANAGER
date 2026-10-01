<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Rules\PhoneNumberRule;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Profil du client : informations, mot de passe, et informations à compléter à la première connexion.
 */
class ProfileController extends Controller
{
    /**
     * Pays proposés dans les formulaires.
     */
    public const COUNTRIES = ['Côte d’Ivoire', 'Sénégal', 'Mali', 'Burkina Faso', 'Togo', 'Bénin', 'Guinée', 'Niger', 'Ghana', 'Nigeria', 'Cameroun', 'France', 'Belgique', 'Canada', 'Autre'];

    public function edit(Request $request): View
    {
        return view('client.profile', ['user' => $request->user(), 'countries' => self::COUNTRIES]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $this->validateInformation($request, withIdentity: true);

        $emailChanged = $validated['email'] !== $user->email;

        $user->update([
            'name' => $validated['nom'],
            'email' => $validated['email'],
            'indicatif_telephone' => $validated['indicatif_telephone'],
            'phone' => $validated['telephone'],
            'city' => $validated['ville'],
            'country' => $validated['pays'],
        ]);

        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null])->save();
            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice')->with('success', 'Confirmez votre nouvelle adresse email : un lien vient de vous être envoyé.');
        }

        return back()->with('success', 'Vos informations sont enregistrées.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            // Un compte créé avec Google / Facebook n'a jamais eu de mot de passe connu
            'mot_de_passe_actuel' => $user->usesSocialLogin() ? ['nullable'] : ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'mot_de_passe_actuel.required' => 'Indiquez votre mot de passe actuel.',
            'mot_de_passe_actuel.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ]);

        $user->forceFill(['password' => Hash::make((string) $request->input('password'))])->save();

        return back()->with('success', 'Votre mot de passe a été modifié.');
    }

    /**
     * Informations manquantes après une inscription avec Google / Facebook.
     */
    public function complete(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->needsProfileCompletion()) {
            return redirect()->intended(route('client.dashboard'));
        }

        return view('client.complete-profile', ['user' => $user, 'countries' => self::COUNTRIES]);
    }

    public function storeCompletion(Request $request): RedirectResponse
    {
        $validated = $this->validateInformation($request, withIdentity: false);

        $request->user()->update([
            'indicatif_telephone' => $validated['indicatif_telephone'],
            'phone' => $validated['telephone'],
            'city' => $validated['ville'],
            'country' => $validated['pays'],
        ]);

        return redirect()->intended(route('client.dashboard'))->with('success', 'Merci, votre compte est prêt. Bienvenue sur DS HOLDING !');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateInformation(Request $request, bool $withIdentity): array
    {
        $request->merge([
            'indicatif_telephone' => $request->input('indicatif_telephone') ?: PhoneNumber::defaultDial(),
            'telephone' => PhoneNumber::normalize((string) $request->input('indicatif_telephone'), (string) $request->input('telephone')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        return $request->validate([
            ...($withIdentity ? [
                'nom' => ['required', 'string', 'max:191'],
                'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($request->user())],
            ] : []),
            'indicatif_telephone' => ['required', Rule::in(array_keys(config('phone.countries')))],
            'telephone' => ['required', new PhoneNumberRule],
            'ville' => ['required', 'string', 'max:100'],
            'pays' => ['required', 'string', 'max:100'],
        ], [
            'telephone.required' => 'Votre numéro permet à l’établissement de vous joindre pour votre arrivée.',
            'ville.required' => 'Indiquez votre ville.',
            'pays.required' => 'Indiquez votre pays.',
            'email.unique' => 'Cette adresse email est déjà utilisée par un autre compte.',
        ]);
    }
}
