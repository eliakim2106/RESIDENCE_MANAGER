<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Inscription à la lettre d'information (pied de page) et désinscription par lien personnel.
 */
class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('newsletter', [
            'email_newsletter' => ['required', 'email', 'max:191'],
        ], [
            'email_newsletter.required' => 'Indiquez votre adresse email.',
            'email_newsletter.email' => 'Cette adresse email n’est pas valide.',
        ]);

        $subscriber = NewsletterSubscriber::query()->firstOrNew(['email' => mb_strtolower(trim($validated['email_newsletter']))]);
        $subscriber->token ??= Str::random(48);
        $subscriber->unsubscribed_at = null;
        $subscriber->save();

        // Même message que l'adresse soit nouvelle ou déjà inscrite
        return back()->with('newsletter_sent', true)->withFragment('newsletter');
    }

    public function unsubscribe(string $token): View
    {
        $subscriber = NewsletterSubscriber::query()->where('token', $token)->firstOrFail();
        $subscriber->forceFill(['unsubscribed_at' => $subscriber->unsubscribed_at ?? now()])->save();

        return view('site.pages.newsletter-unsubscribed', ['email' => $subscriber->email]);
    }
}
