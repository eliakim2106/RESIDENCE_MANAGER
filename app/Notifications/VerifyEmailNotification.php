<?php

namespace App\Notifications;

use App\Notifications\Concerns\SendsMailInBackground;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Email de confirmation d'adresse, envoyé en arrière-plan (son contenu est défini dans AppServiceProvider).
 */
class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use SendsMailInBackground;
}
