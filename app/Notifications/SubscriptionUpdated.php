<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Événements de l'abonnement d'un propriétaire : début d'essai, facture émise, paiement reçu, suspension.
 */
class SubscriptionUpdated extends Notification
{
    public const TRIAL_STARTED = 'trial_started';

    public const INVOICE_ISSUED = 'invoice_issued';

    public const INVOICE_PAID = 'invoice_paid';

    public const SUSPENDED = 'suspended';

    /** Facture à payer bientôt (quelques jours avant l'échéance) */
    public const INVOICE_REMINDER = 'invoice_reminder';

    public function __construct(
        public Subscription $subscription,
        public string $event,
        public ?SubscriptionInvoice $invoice = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plan = $this->subscription->plan;
        $invoice = $this->invoice;

        $mail = (new MailMessage)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->salutation("Cordialement,\nL’équipe DS Holding");

        match ($this->event) {
            self::TRIAL_STARTED => $mail
                ->subject('Votre essai gratuit commence')
                ->line('Votre essai gratuit de la formule **'.$plan->name.'** est ouvert jusqu’au '.$this->subscription->trial_ends_at->translatedFormat('d F Y').'.')
                ->line('Profitez-en pour publier vos établissements et recevoir vos premières réservations.'),

            self::INVOICE_ISSUED => $mail
                ->subject('Facture '.$invoice->number.' – abonnement '.$plan->name)
                ->line('Votre facture **'.$invoice->number.'** de **'.$this->money($invoice->amount).'** est disponible.')
                ->line('Période du '.$invoice->period_start->format('d/m/Y').' au '.$invoice->period_end->format('d/m/Y').'.')
                ->line('À régler avant le **'.$invoice->due_on->translatedFormat('d F Y').'** pour que vos établissements restent en ligne.'),

            self::INVOICE_PAID => $mail
                ->subject('Paiement reçu – '.$invoice->number)
                ->line('Nous avons bien reçu votre paiement de **'.$this->money($invoice->amount).'** pour la facture '.$invoice->number.'. Merci !'),

            self::INVOICE_REMINDER => $mail
                ->subject('Rappel : facture '.$invoice->number.' à régler avant le '.$invoice->due_on->format('d/m/Y'))
                ->line('Votre facture **'.$invoice->number.'** de **'.$this->money($invoice->amount).'** arrive à échéance le **'.$invoice->due_on->translatedFormat('d F Y').'**.')
                ->line('Sans paiement à cette date, votre abonnement sera suspendu et vos établissements ne seront plus visibles sur le site.'),

            self::SUSPENDED => $mail
                ->subject('Abonnement suspendu')
                ->line('Votre abonnement **'.$plan->name.'** est suspendu faute de paiement.')
                ->line('Réglez votre facture en attente pour le réactiver.'),

            default => $mail->subject('Votre abonnement'),
        };

        return $mail->action('Voir mon abonnement', route('admin.abonnement.show'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        [$message, $icon, $tone] = match ($this->event) {
            self::TRIAL_STARTED => ['Votre essai gratuit « '.$this->subscription->plan->name.' » a commencé.', 'fa-gift', 'info'],
            self::INVOICE_ISSUED => ['Nouvelle facture '.$this->invoice?->number.' : '.$this->money((int) $this->invoice?->amount).' à régler.', 'fa-file-invoice', 'warning'],
            self::INVOICE_PAID => ['Paiement reçu pour la facture '.$this->invoice?->number.'.', 'fa-circle-check', 'good'],
            self::INVOICE_REMINDER => ['Rappel : la facture '.$this->invoice?->number.' ('.$this->money((int) $this->invoice?->amount).') est à régler avant le '.$this->invoice?->due_on->format('d/m/Y').'.', 'fa-clock', 'warning'],
            self::SUSPENDED => ['Votre abonnement est suspendu faute de paiement.', 'fa-ban', 'critical'],
            default => ['Votre abonnement a été mis à jour.', 'fa-id-card', 'info'],
        };

        return ['message' => $message, 'icon' => $icon, 'tone' => $tone, 'url' => route('admin.abonnement.show')];
    }

    private function money(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}
