<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email « Mot de passe oublié » : lien de réinitialisation, valable une heure.
 */
class ResetPasswordNotification extends Notification
{
    public function __construct(public string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe DS HOLDING')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Vous avez demandé à changer le mot de passe de votre compte DS HOLDING.')
            ->action('Choisir un nouveau mot de passe', $url)
            ->line('Ce lien est valable '.config('auth.passwords.users.expire').' minutes et ne peut servir qu’une fois.')
            ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez cet email : votre mot de passe actuel reste inchangé.')
            ->salutation("Cordialement,\nL’équipe DS Holding");
    }
}
