<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Reversement envoyé au propriétaire par DS Holding.
 */
class PayoutRecorded extends Notification
{
    public function __construct(public Payout $payout) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payout = $this->payout;

        $mail = (new MailMessage)
            ->subject('Reversement '.$payout->number.' – '.$this->money($payout->amount))
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('DS Holding vous a reversé **'.$this->money($payout->amount).'** le '.$payout->paid_at->translatedFormat('d F Y').' par '.$payout->method->label().'.');

        if ($payout->commission_amount > 0) {
            $mail->line('Montant encaissé : '.$this->money($payout->gross_amount).', commission : '.$this->money($payout->commission_amount).'.');
        }

        if ($payout->reference) {
            $mail->line('Référence du virement : '.$payout->reference.'.');
        }

        return $mail
            ->action('Voir le relevé', route('admin.reversements.statement', $payout))
            ->salutation("Cordialement,\nL’équipe DS Holding");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Reversement '.$this->payout->number.' de '.$this->money($this->payout->amount).' envoyé.',
            'icon' => 'fa-hand-holding-dollar',
            'tone' => 'good',
            'url' => route('admin.mes-reversements.index'),
        ];
    }

    private function money(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}
