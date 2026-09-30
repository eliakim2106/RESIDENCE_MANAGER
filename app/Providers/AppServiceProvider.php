<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Email de confirmation d'adresse envoyé après l'inscription
        VerifyEmail::toMailUsing(fn (object $notifiable, string $url): MailMessage => (new MailMessage)
            ->subject('Confirmez votre adresse email · DS HOLDING')
            ->greeting('Bonjour '.Str::before((string) $notifiable->name, ' ').',')
            ->line('Merci d’avoir créé votre compte DS HOLDING. Pour l’activer, confirmez votre adresse email en cliquant sur le bouton ci-dessous.')
            ->action('Confirmer mon adresse email', $url)
            ->line('Ce lien est valable 60 minutes.')
            ->line('Si vous n’êtes pas à l’origine de cette inscription, ignorez simplement ce message.')
            ->salutation('L’équipe DS HOLDING'));

        Password::defaults(fn (): Password => $this->app->isProduction()
            ? Password::min(8)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8)->letters()->numbers());
    }
}
