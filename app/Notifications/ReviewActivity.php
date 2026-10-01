<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Vie d'un avis, signalée dans l'application :
 * nouvel avis (propriétaire), réponse de l'établissement (voyageur), signalement (administrateurs),
 * avis masqué (propriétaire et voyageur), avis republié (propriétaire).
 */
class ReviewActivity extends Notification
{
    public const NEW_FOR_OWNER = 'new_for_owner';

    public const REPLIED_FOR_GUEST = 'replied_for_guest';

    public const REPORTED_FOR_ADMIN = 'reported_for_admin';

    public const HIDDEN_FOR_OWNER = 'hidden_for_owner';

    public const HIDDEN_FOR_GUEST = 'hidden_for_guest';

    public const PUBLISHED_FOR_OWNER = 'published_for_owner';

    public function __construct(
        public Review $review,
        public string $event,
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
        $review = $this->review;
        $property = $review->property?->name ?? 'votre établissement';
        $note = $review->rating.'/10';

        [$message, $icon, $tone] = match ($this->event) {
            self::NEW_FOR_OWNER => ["Nouvel avis {$note} de {$review->authorName()} sur {$property}.", 'fa-star', $review->rating >= 7 ? 'good' : 'warning'],
            self::REPLIED_FOR_GUEST => ["{$property} a répondu à votre avis.", 'fa-reply', 'info'],
            self::REPORTED_FOR_ADMIN => [($review->reporter?->name ?? 'Un propriétaire')." signale un avis sur {$property} : « ".Str::limit((string) $review->report_reason, 70).' ».', 'fa-flag', 'warning'],
            self::HIDDEN_FOR_OWNER => ["DS HOLDING a masqué l’avis de {$review->authorName()} sur {$property}.", 'fa-eye-slash', 'info'],
            self::HIDDEN_FOR_GUEST => ["Votre avis sur {$property} a été retiré par la modération.", 'fa-eye-slash', 'warning'],
            self::PUBLISHED_FOR_OWNER => ["L’avis de {$review->authorName()} sur {$property} est maintenu en ligne.", 'fa-eye', 'info'],
            default => ["Avis sur {$property} mis à jour.", 'fa-star', 'info'],
        };

        // Le voyageur suit son avis depuis sa réservation ; l'établissement et les administrateurs, dans l'administration
        $url = in_array($this->event, [self::REPLIED_FOR_GUEST, self::HIDDEN_FOR_GUEST], true)
            ? route('client.reservations.show', $review->reservation)
            : route('admin.avis.show', $review);

        return ['message' => $message, 'icon' => $icon, 'tone' => $tone, 'url' => $url];
    }
}
