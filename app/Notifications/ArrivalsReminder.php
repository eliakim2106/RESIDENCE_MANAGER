<?php

namespace App\Notifications;

use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Rappel quotidien au propriétaire : les clients qui arrivent demain dans ses établissements.
 */
class ArrivalsReminder extends Notification
{
    /**
     * @param  Collection<int, Reservation>  $reservations  avec property
     */
    public function __construct(
        public Collection $reservations,
        public CarbonImmutable $day,
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
        $count = $this->reservations->count();

        $mail = (new MailMessage)
            ->subject($count.' arrivée'.($count > 1 ? 's' : '').' le '.$this->day->translatedFormat('l d F'))
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Voici les clients attendus '.$this->day->translatedFormat('l d F Y').' :');

        foreach ($this->reservations as $reservation) {
            $mail->line('• **'.$reservation->guest_name.'** – '.$reservation->property?->name.' – '.$reservation->nights.' nuit'.($reservation->nights > 1 ? 's' : '')
                .' ('.$reservation->reference.($reservation->balanceDue() > 0 ? ', reste '.$this->money($reservation->balanceDue()).' à encaisser' : '').')');
        }

        return $mail
            ->action('Voir le calendrier', route('admin.reservations.calendar'))
            ->salutation("Bon accueil !\nL’équipe DS Holding");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $count = $this->reservations->count();
        $names = $this->reservations->take(2)->pluck('guest_name')->implode(', ').($count > 2 ? ' et '.($count - 2).' autre'.($count > 3 ? 's' : '') : '');

        return [
            'message' => "{$count} arrivée".($count > 1 ? 's' : '')." demain : {$names}.",
            'icon' => 'fa-suitcase-rolling',
            'tone' => 'info',
            'url' => $count === 1
                ? route('admin.reservations.show', $this->reservations->first())
                : route('admin.reservations.index', ['statut' => 'confirmees', 'du' => $this->day->toDateString(), 'au' => $this->day->toDateString()]),
        ];
    }

    private function money(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}
