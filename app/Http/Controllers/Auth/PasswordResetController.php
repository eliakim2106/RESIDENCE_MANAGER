<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Mot de passe oublié : demande d'un lien par email, puis choix d'un nouveau mot de passe.
 *
 * La réponse à la demande est toujours la même, que l'adresse corresponde à un compte ou non :
 * on ne révèle pas qui est inscrit sur la plateforme.
 */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $request->validate(
            ['email' => ['required', 'email']],
            ['email.required' => 'Indiquez l’adresse email de votre compte.', 'email.email' => 'Cette adresse email n’est pas valide.'],
        );

        $status = Password::sendResetLink($request->only('email'));

        // Lien déjà demandé il y a moins d'une minute : on le signale, sans rien révéler d'autre
        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->with('error', 'Un lien vient déjà d’être envoyé. Patientez une minute avant d’en demander un nouveau.');
        }

        return back()->with('success', 'Si un compte existe pour '.$request->input('email').', un lien de réinitialisation vient de lui être envoyé. Il est valable '.config('auth.passwords.users.expire').' minutes.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], [
            'password.required' => 'Choisissez un nouveau mot de passe.',
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    // Le lien reçu par email prouve que l'adresse est bien la sienne
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => match ($status) {
                    Password::INVALID_TOKEN => 'Ce lien n’est plus valable : il a expiré ou a déjà servi. Demandez-en un nouveau.',
                    default => 'Aucun compte ne correspond à cette adresse email.',
                },
            ]);
        }

        return redirect()->route('login')->with('success', 'Votre mot de passe a été modifié. Vous pouvez vous connecter.');
    }
}
