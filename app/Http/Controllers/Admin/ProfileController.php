<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * « Mon profil » : informations, photo et mot de passe du compte connecté (tous les rôles).
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('admin.profil.edit', [
            'user' => $user,
            'logins' => $user->loginLogs()->latest('id')->limit(5)->get(),
        ]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->profileAttributes());

        // Nouvelle adresse : elle doit être confirmée avant de retrouver l'accès à l'espace
        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('photo') || $request->boolean('supprimer_photo')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->avatar_path = $request->hasFile('photo')
                ? $request->file('photo')->store("avatars/{$user->id}", 'public')
                : null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice')
                ->with('success', 'Adresse modifiée : un lien de confirmation vient d’être envoyé à '.$user->email.'.');
        }

        return back()->with('success', 'Profil mis à jour.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'mot_de_passe_actuel' => ['required', 'current_password'],
            'mot_de_passe' => ['required', 'confirmed', Password::defaults()],
        ], [
            'mot_de_passe_actuel.required' => 'Indiquez votre mot de passe actuel.',
            'mot_de_passe_actuel.current_password' => 'Le mot de passe actuel est incorrect.',
            'mot_de_passe.required' => 'Choisissez un nouveau mot de passe.',
            'mot_de_passe.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
        ]);

        $request->user()->update(['password' => $validated['mot_de_passe']]);

        return back()->with('success', 'Mot de passe modifié.');
    }
}
