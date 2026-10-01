<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use Illuminate\Notifications\Notification;

/**
 * Alerte aux administrateurs sur les abonnements : facture réglée en ligne, abonnement suspendu pour impayé.
 */
class AdminSubscriptionAlert extends Notification
{
    public const INVOICE_PAID_ONLINE = 'invoice_paid_online';

    public const SUSPENDED = 'suspended';

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
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $owner = $this->subscription->user?->name ?? 'Un propriétaire';

        [$message, $icon, $tone] = match ($this->event) {
            self::INVOICE_PAID_ONLINE => ["{$owner} a réglé en ligne la facture {$this->invoice?->number} ({$this->money((int) $this->invoice?->amount)}).", 'fa-file-circle-check', 'good'],
            self::SUSPENDED => ["Abonnement de {$owner} suspendu faute de paiement : ses établissements ne sont plus visibles.", 'fa-ban', 'critical'],
            default => ["L’abonnement de {$owner} a changé.", 'fa-id-card', 'info'],
        };

        return ['message' => $message, 'icon' => $icon, 'tone' => $tone, 'url' => route('admin.abonnements.show', $this->subscription)];
    }

    private function money(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}
