<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Notifications du compte connecté (menu cloche de la barre du haut).
 */
class NotificationController extends Controller
{
    /**
     * Marque la notification comme lue puis ouvre la page concernée.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // Uniquement une adresse de l'application
        return is_string($url) && str_starts_with($url, url('/'))
            ? redirect()->to($url)
            : redirect()->route('dashboard');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
