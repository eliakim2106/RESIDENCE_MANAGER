<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Connexion des clients avec Google ou Facebook.
 *
 * 1. Le client choisit son compte chez le fournisseur, qui vérifie son identité et son adresse email.
 * 2. Au retour : compte DS Holding retrouvé (identifiant du fournisseur, puis adresse email) ou créé.
 * 3. À la première connexion, il complète les informations manquantes (téléphone, ville, pays).
 *
 * Un compte propriétaire ou administrateur ne se connecte pas par ce moyen : son mot de passe reste exigé.
 */
class SocialLoginController extends Controller
{
    /**
     * Fournisseurs proposés : libellé et icône.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const PROVIDERS = [
        'google' => ['Google', 'fa-google'],
        'facebook' => ['Facebook', 'fa-facebook-f'],
    ];

    /**
     * Fournisseurs configurés (identifiant et secret renseignés dans .env).
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function enabled(): array
    {
        return array_filter(
            self::PROVIDERS,
            fn (string $provider): bool => filled(config("services.{$provider}.client_id")) && filled(config("services.{$provider}.client_secret")),
            ARRAY_FILTER_USE_KEY,
        );
    }

    public function redirect(string $provider): RedirectResponse|SymfonyRedirect
    {
        abort_unless(isset(self::PROVIDERS[$provider]), 404);

        if (! isset(self::enabled()[$provider])) {
            return redirect()->route('login')->with('error', 'La connexion avec '.self::PROVIDERS[$provider][0].' n’est pas encore disponible. Utilisez votre adresse email.');
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(isset(self::PROVIDERS[$provider]), 404);
        $label = self::PROVIDERS[$provider][0];

        try {
            $social = Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            Log::info("Connexion {$label} interrompue", ['error' => $exception->getMessage()]);

            return redirect()->route('login')->with('error', "La connexion avec {$label} a été annulée ou a échoué. Réessayez, ou connectez-vous avec votre email.");
        }

        $email = mb_strtolower(trim((string) $social->getEmail()));

        if ($email === '') {
            return redirect()->route('login')->with('error', "{$label} ne nous a pas transmis votre adresse email : autorisez son partage, ou créez votre compte avec votre email.");
        }

        $user = $this->findOrCreate($provider, $social, $email);

        if (is_string($user)) {
            return redirect()->route('login')->with('error', $user);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return $user->needsProfileCompletion()
            ? redirect()->route('client.profile.complete')
            : redirect()->intended(route('client.dashboard'));
    }

    /**
     * Compte DS Holding correspondant, ou message expliquant pourquoi la connexion est refusée.
     */
    private function findOrCreate(string $provider, SocialUser $social, string $email): User|string
    {
        $column = "{$provider}_id";
        $user = User::query()->where($column, $social->getId())->first()
            ?? User::query()->where('email', $email)->first();

        if ($user) {
            if (! $user->hasRole(UserRole::Client)) {
                return 'Ce compte DS Holding est un compte '.mb_strtolower($user->role->label()).' : connectez-vous avec votre email et votre mot de passe.';
            }

            if ($user->statut === UserStatus::Suspended) {
                return 'Ce compte est suspendu. Contactez l’équipe DS Holding.';
            }

            // Compte existant relié au fournisseur ; l'adresse est confirmée par celui-ci
            $user->forceFill([
                $column => $social->getId(),
                'email_verified_at' => $user->email_verified_at ?? now(),
                'social_avatar' => $user->social_avatar ?: $social->getAvatar(),
            ])->save();

            return $user;
        }

        $user = new User([
            'name' => trim((string) ($social->getName() ?: Str::before($email, '@'))),
            'email' => $email,
            'role' => UserRole::Client,
            'statut' => UserStatus::Active,
            'social_avatar' => $social->getAvatar(),
            // Mot de passe aléatoire : le client peut en choisir un avec « Mot de passe oublié »
            'password' => Str::password(32),
        ]);
        $user->forceFill([$column => $social->getId(), 'email_verified_at' => now()])->save();

        return $user;
    }
}
