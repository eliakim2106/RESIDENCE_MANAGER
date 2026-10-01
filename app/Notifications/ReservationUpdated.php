<?php

namespace App\Notifications;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Changement sur une réservation : email au client (et notification dans son espace),
 * ou alerte au propriétaire quand le client annule lui-même.
 */
class ReservationUpdated extends Notification
{
    public const CONFIRMED = 'confirmed';

    public const REFUSED = 'refused';

    public const CANCELLED = 'cancelled';

    public const CANCELLED_BY_GUEST = 'cancelled_by_guest';

    public const REFUNDED = 'refunded';

    /** Paiement en ligne reçu : reçu au client */
    public const PAID = 'paid';

    /** Paiement en ligne reçu : alerte au propriétaire */
    public const PAID_FOR_OWNER = 'paid_for_owner';

    public function __construct(
        public Reservation $reservation,
        public string $event,
        public ?int $amount = null,
    ) {}

    /**
     * Un compte reçoit l'email et la notification ; un voyageur sans compte, l'email seul.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        // Le propriétaire est prévenu dans son espace ; le client, par email aussi
        if (in_array($this->event, [self::CANCELLED_BY_GUEST, self::PAID_FOR_OWNER], true)) {
            return ['database'];
        }

        return $notifiable instanceof User ? ['mail', 'database'] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reservation = $this->reservation;
        $property = $reservation->property;
        $stay = 'du '.$reservation->check_in->translatedFormat('d F Y').' au '.$reservation->check_out->translatedFormat('d F Y');
        $reason = $reservation->cancellation_reason;

        $mail = (new MailMessage)
            ->greeting('Bonjour '.$reservation->guest_name.',')
            ->salutation("Cordialement,\nL’équipe DS Holding");

        match ($this->event) {
            self::CONFIRMED => $mail
                ->subject('Réservation confirmée – '.$reservation->reference)
                ->line('Bonne nouvelle : votre réservation **'.$reservation->reference.'** à **'.$property?->name.'** est confirmée.')
                ->line('Séjour '.$stay.' ('.$reservation->nights.' nuit'.($reservation->nights > 1 ? 's' : '').').')
                ->line($reservation->balanceDue() > 0
                    ? 'Reste à régler : '.$this->money($reservation->balanceDue()).'.'
                    : 'Votre séjour est entièrement réglé.'),

            self::REFUSED => $mail
                ->subject('Réservation non acceptée – '.$reservation->reference)
                ->line('Nous sommes désolés : l’établissement **'.$property?->name.'** ne peut pas accepter votre demande '.$stay.'.')
                ->lineIf(filled($reason), 'Motif : '.$reason)
                ->line('D’autres résidences sont peut-être disponibles à ces dates.'),

            self::CANCELLED => $mail
                ->subject('Réservation annulée – '.$reservation->reference)
                ->line('Votre réservation **'.$reservation->reference.'** à **'.$property?->name.'** ('.$stay.') a été annulée.')
                ->lineIf(filled($reason), 'Motif : '.$reason),

            self::REFUNDED => $mail
                ->subject('Remboursement – '.$reservation->reference)
                ->line('Un remboursement de **'.$this->money((int) $this->amount).'** a été enregistré pour votre réservation **'.$reservation->reference.'**.')
                ->line('Il vous est reversé par le moyen utilisé lors du paiement.'),

            self::PAID => $mail
                ->subject('Paiement reçu – '.$reservation->reference)
                ->line('Nous avons bien reçu votre paiement de **'.$this->money((int) $this->amount).'** pour la réservation **'.$reservation->reference.'** à **'.$property?->name.'**. Merci !')
                ->line($reservation->balanceDue() > 0
                    ? 'Reste à régler : '.$this->money($reservation->balanceDue()).'.'
                    : 'Votre séjour est entièrement réglé.'),

            default => $mail->subject('Réservation '.$reservation->reference),
        };

        return $mail->action('Voir ma réservation', route('admin.reservations.show', $reservation));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $reference = $this->reservation->reference;

        [$message, $icon, $tone] = match ($this->event) {
            self::CONFIRMED => ["Votre réservation {$reference} est confirmée.", 'fa-circle-check', 'good'],
            self::REFUSED => ["Votre réservation {$reference} n’a pas été acceptée.", 'fa-circle-xmark', 'critical'],
            self::CANCELLED => ["Votre réservation {$reference} a été annulée.", 'fa-ban', 'critical'],
            self::CANCELLED_BY_GUEST => ["{$this->reservation->guest_name} a annulé la réservation {$reference}.", 'fa-ban', 'warning'],
            self::REFUNDED => ["Remboursement de {$this->money((int) $this->amount)} enregistré ({$reference}).", 'fa-rotate-left', 'info'],
            self::PAID => ["Paiement de {$this->money((int) $this->amount)} reçu pour la réservation {$reference}.", 'fa-circle-check', 'good'],
            self::PAID_FOR_OWNER => ["{$this->reservation->guest_name} a payé {$this->money((int) $this->amount)} en ligne ({$reference}).", 'fa-wallet', 'good'],
            default => ["Réservation {$reference} mise à jour.", 'fa-calendar-check', 'info'],
        };

        return [
            'message' => $message,
            'icon' => $icon,
            'tone' => $tone,
            'url' => route('admin.reservations.show', $this->reservation),
        ];
    }

    private function money(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}
