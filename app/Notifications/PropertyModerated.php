<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Notifications\Notification;

/**
 * Informe le propriétaire de la décision prise sur son établissement.
 */
class PropertyModerated extends Notification
{
    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const SUSPENDED = 'suspended';

    public const REINSTATED = 'reinstated';

    public function __construct(public Property $property, public string $decision) {}

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
        $name = $this->property->name;

        [$message, $icon, $tone] = match ($this->decision) {
            self::APPROVED => ["« {$name} » est validé et publié sur le site.", 'fa-circle-check', 'good'],
            self::REJECTED => ["« {$name} » n’a pas été validé. Consultez le motif et modifiez-le.", 'fa-circle-xmark', 'critical'],
            self::SUSPENDED => ["« {$name} » a été suspendu. Consultez le motif.", 'fa-ban', 'critical'],
            default => ["« {$name} » est de nouveau publié.", 'fa-rotate-left', 'good'],
        };

        return [
            'message' => $message,
            'icon' => $icon,
            'tone' => $tone,
            'url' => route('admin.etablissements.edit', $this->property),
        ];
    }
}
