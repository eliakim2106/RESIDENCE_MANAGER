<?php

namespace App\Notifications;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\Concerns\SendsMailInBackground;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Changement sur une réservation : email au client (et notification dans son espace),
 * ou alerte au propriétaire quand le client annule lui-même.
 */
class ReservationUpdated extends Notification implements ShouldQueue
{
    use SendsMailInBackground;

    public const CONFIRMED = 'confirmed';

    public const REFUSED = 'refused';

    public const CANCELLED = 'cancelled';

    public const CANCELLED_BY_GUEST = 'cancelled_by_guest';

    public const REFUNDED = 'refunded';

    /** Paiement en ligne reçu : reçu au client */
    public const PAID = 'paid';

    /** Paiement en ligne reçu : alerte au propriétaire */
    public const PAID_FOR_OWNER = 'paid_for_owner';

    /** Nouvelle réservation (à valider, ou confirmée d'office) : alerte au propriétaire */
    public const NEW_FOR_OWNER = 'new_for_owner';

    /** Demande restée sans réponse dans le délai : au client */
    public const EXPIRED = 'expired';

    /** Demande restée sans réponse dans le délai : alerte au propriétaire */
    public const EXPIRED_FOR_OWNER = 'expired_for_owner';

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
        if (in_array($this->event, [self::CANCELLED_BY_GUEST, self::PAID_FOR_OWNER, self::EXPIRED_FOR_OWNER], true)) {
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
            ->greeting('Bonjour '.($this->event === self::NEW_FOR_OWNER ? $notifiable->name : $reservation->guest_name).',')
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

            self::EXPIRED => $mail
                ->subject('Demande expirée – '.$reservation->reference)
                ->line('L’établissement **'.$property?->name.'** n’a pas répondu à temps à votre demande '.$stay.' : elle a expiré et les dates ne vous sont plus réservées.')
                ->lineIf((int) $this->amount > 0, 'Le paiement de '.$this->money((int) $this->amount).' que vous avez effectué vous est remboursé.')
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

            self::NEW_FOR_OWNER => $mail
                ->subject(($reservation->statut === ReservationStatus::Pending ? 'Nouvelle demande de réservation' : 'Nouvelle réservation').' – '.$reservation->reference)
                ->line('**'.$reservation->guest_name.'** '.($reservation->statut === ReservationStatus::Pending ? 'demande à réserver' : 'a réservé').' **'.$property?->name.'** '.$stay.' ('.$reservation->nights.' nuit'.($reservation->nights > 1 ? 's' : '').', '.($reservation->adults + $reservation->children).' voyageur'.($reservation->adults + $reservation->children > 1 ? 's' : '').').')
                ->line('Montant : '.$this->money($reservation->total_amount).'.')
                ->lineIf($reservation->statut === ReservationStatus::Pending, 'Validez ou refusez la demande depuis votre espace : le client attend votre réponse.'),

            default => $mail->subject('Réservation '.$reservation->reference),
        };

        return $mail->action(
            $this->event === self::NEW_FOR_OWNER ? 'Voir la réservation' : 'Voir ma réservation',
            $this->url($notifiable),
        );
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
            self::EXPIRED => ["Votre demande {$reference} a expiré sans réponse de l’établissement.", 'fa-hourglass-end', 'critical'],
            self::EXPIRED_FOR_OWNER => ["La demande de {$this->reservation->guest_name} ({$reference}) a expiré faute de réponse.", 'fa-hourglass-end', 'warning'],
            self::REFUNDED => ["Remboursement de {$this->money((int) $this->amount)} enregistré ({$reference}).", 'fa-rotate-left', 'info'],
            self::PAID => ["Paiement de {$this->money((int) $this->amount)} reçu pour la réservation {$reference}.", 'fa-circle-check', 'good'],
            self::PAID_FOR_OWNER => ["{$this->reservation->guest_name} a payé {$this->money((int) $this->amount)} en ligne ({$reference}).", 'fa-wallet', 'good'],
            self::NEW_FOR_OWNER => $this->reservation->statut === ReservationStatus::Pending
                ? ["Nouvelle demande de {$this->reservation->guest_name} ({$reference}) : à valider.", 'fa-calendar-plus', 'warning']
                : ["Nouvelle réservation de {$this->reservation->guest_name} ({$reference}).", 'fa-calendar-plus', 'good'],
            default => ["Réservation {$reference} mise à jour.", 'fa-calendar-check', 'info'],
        };

        return [
            'message' => $message,
            'icon' => $icon,
            'tone' => $tone,
            'url' => $this->url($notifiable),
        ];
    }

    /**
     * Le client suit sa réservation dans son espace ; l'établissement, dans l'administration.
     */
    private function url(object $notifiable): string
    {
        $forOwner = in_array($this->event, [self::NEW_FOR_OWNER, self::PAID_FOR_OWNER, self::CANCELLED_BY_GUEST, self::EXPIRED_FOR_OWNER], true);

        return $forOwner || ($notifiable instanceof User && ! $notifiable->isClient())
            ? route('admin.reservations.show', $this->reservation)
            : route('client.reservations.show', $this->reservation);
    }

    private function money(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}
