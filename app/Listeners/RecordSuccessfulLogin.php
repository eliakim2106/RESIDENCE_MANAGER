<?php

namespace App\Listeners;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

class RecordSuccessfulLogin
{
    public function __construct(private Request $request) {}

    /**
     * Journalise la connexion et met à jour la date de dernière connexion.
     */
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill(['last_login_at' => now()])->save();

        LoginLog::create([
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'successful' => true,
        ]);
    }
}
