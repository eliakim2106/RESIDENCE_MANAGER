<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Inscription en deux temps : choix du profil (client ou propriétaire), puis formulaire dédié.
 */
class RegisterController extends Controller
{
    public function choice(): View
    {
        return view('auth.register');
    }

    public function createClient(): View
    {
        return view('auth.register-client');
    }

    public function storeClient(RegisterRequest $request): RedirectResponse
    {
        return $this->register($request, UserRole::Client);
    }

    public function createOwner(): View
    {
        return view('auth.register-owner');
    }

    public function storeOwner(RegisterRequest $request): RedirectResponse
    {
        return $this->register($request, UserRole::Owner);
    }

    private function register(RegisterRequest $request, UserRole $role): RedirectResponse
    {
        $user = User::create([
            ...$request->userAttributes(),
            'role' => $role,
            'statut' => UserStatus::Active,
        ]);

        // Envoie le lien de confirmation par email (le compte n'accède à son espace qu'une fois l'adresse confirmée)
        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }
}
