<?php

namespace App\Listeners;

use App\Models\LoginLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;

class RecordFailedLogin
{
    public function __construct(private Request $request) {}

    /**
     * Journalise une tentative de connexion échouée.
     */
    public function handle(Failed $event): void
    {
        LoginLog::create([
            'user_id' => $event->user?->getAuthIdentifier(),
            'email' => (string) ($event->credentials['email'] ?? ''),
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'successful' => false,
        ]);
    }
}
