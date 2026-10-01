<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Message reçu par le formulaire de contact du site, signalé aux administrateurs.
 */
class NewContactMessage extends Notification
{
    public function __construct(public ContactMessage $contactMessage) {}

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
            'message' => "Nouveau message de {$this->contactMessage->name} : « ".Str::limit($this->contactMessage->subject, 60).' ».',
            'icon' => 'fa-envelope',
            'tone' => 'info',
            'url' => route('admin.messages.show', $this->contactMessage),
        ];
    }
}
