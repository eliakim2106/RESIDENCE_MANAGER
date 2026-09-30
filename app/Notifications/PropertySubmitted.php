<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Notifications\Notification;

/**
 * Prévient les administrateurs qu'un établissement attend leur validation.
 */
class PropertySubmitted extends Notification
{
    public function __construct(public Property $property) {}

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
        return [
            'message' => "« {$this->property->name} » attend votre validation.",
            'icon' => 'fa-building-circle-check',
            'tone' => 'warning',
            'url' => route('admin.validations.index'),
        ];
    }
}
