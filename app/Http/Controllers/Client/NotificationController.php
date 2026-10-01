<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Notifications du client (confirmation, paiement reçu, remboursement…). Ouverture et lecture : NotificationController de l'administration.
 */
class NotificationController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('client.notifications', [
            'notifications' => $request->user()->notifications()->latest()->paginate(15),
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
