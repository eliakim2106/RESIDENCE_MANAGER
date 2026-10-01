<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Confirmation de l'adresse email après l'inscription : page d'attente, lien signé reçu par email, renvoi du lien.
 */
class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($request->user()->homeUrl());
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->intended($request->user()->homeUrl())
            ->with('success', 'Votre adresse email est confirmée. Bienvenue sur DS HOLDING !');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($request->user()->homeUrl());
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Un nouveau lien de confirmation vient de vous être envoyé.');
    }
}
