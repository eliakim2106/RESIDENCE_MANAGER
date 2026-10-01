<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Notifications du compte connecté : menu cloche (mis à jour en direct), page « Notifications » et actions.
 */
class NotificationController extends Controller
{
    /**
     * Nombre de notifications affichées dans le menu cloche.
     */
    public const MENU_LIMIT = 6;

    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = $request->query('statut') === 'toutes' ? 'toutes' : 'non-lues';

        $notifications = ($tab === 'toutes' ? $user->notifications() : $user->unreadNotifications())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'tab' => $tab,
            'counts' => [
                'non-lues' => $user->unreadNotifications()->count(),
                'toutes' => $user->notifications()->count(),
            ],
        ]);
    }

    /**
     * Flux du menu cloche, interrogé régulièrement par la page : compteur et dernières notifications non lues.
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'count' => $user->unreadNotifications()->count(),
            'html' => view('admin.partials.notification-items', [
                'items' => $user->unreadNotifications()->latest()->limit(self::MENU_LIMIT)->get(),
            ])->render(),
        ]);
    }

    /**
     * Marque la notification comme lue puis ouvre la page concernée.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        $target = $this->localPath($notification->data['url'] ?? null);

        return $target ? redirect()->to(url($target)) : redirect()->route('admin.notifications.index');
    }

    public function markRead(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $request->user()->notifications()->findOrFail($notification)->markAsRead();

        return $request->expectsJson() ? response()->json(['ok' => true]) : back();
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back()->with('success', 'Toutes les notifications sont marquées comme lues.');
    }

    public function destroy(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($notification)->delete();

        return back()->with('success', 'Notification supprimée.');
    }

    /**
     * Supprime les notifications déjà lues.
     */
    public function destroyRead(Request $request): RedirectResponse
    {
        $deleted = $request->user()->readNotifications()->delete();

        return back()->with('success', $deleted.' notification'.($deleted > 1 ? 's' : '').' lue'.($deleted > 1 ? 's' : '').' supprimée'.($deleted > 1 ? 's' : '').'.');
    }

    /**
     * Chemin de l'application désigné par le lien enregistré dans la notification.
     *
     * Le lien a été écrit avec l'adresse du site au moment de l'envoi (localhost/…/public, 127.0.0.1:8000,
     * domaine de production…) : seul le chemin compte, rapporté à l'adresse actuelle. Un lien vers un
     * autre site n'est jamais suivi.
     */
    private function localPath(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || (isset($parts['scheme']) && ! in_array($parts['scheme'], ['http', 'https'], true))) {
            return null;
        }

        // Seules les pages de l'espace connecté (/admin…) sont ouvertes depuis une notification. Le préfixe
        // éventuel du site au moment de l'envoi (ex. /DS_HOLDING/RESIDENCE_MANAGER/public) est ignoré.
        if (! preg_match('#^(?:/[^/]+)*?(/admin(?:/.*)?)$#', '/'.ltrim($parts['path'] ?? '', '/'), $matches)) {
            return null;
        }

        return $matches[1].(isset($parts['query']) ? '?'.$parts['query'] : '');
    }
}
