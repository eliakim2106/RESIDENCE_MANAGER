<?php

namespace App\Models;

use App\Enums\PayoutMethod;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Notifications\ResetPasswordNotification;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Override;

/**
 * Les comptes créés depuis le site confirment leur adresse email avant d’accéder à leur espace.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /**
     * Mot de passe des comptes générés par la factory (tests et données de démonstration).
     */
    public const DEFAULT_PASSWORD = 'Residence@2026';

    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'indicatif_telephone',
        'city',
        'country',
        'role',
        'statut',
        'company_name',
        'payout_method',
        'payout_account',
        'payout_holder',
        'subscription_exempt',
        'avatar_path',
        'social_avatar',
        'google_id',
        'facebook_id',
        'password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
        'facebook_id',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'statut' => UserStatus::class,
            'payout_method' => PayoutMethod::class,
            'subscription_exempt' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Établissements gérés par le propriétaire.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'favorites')->withPivot('created_at');
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    /**
     * Abonnements du propriétaire, du plus ancien au plus récent.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Abonnement en cours : le plus récent.
     */
    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * Reversements reçus de DS Holding (propriétaire).
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    /*
    |--------------------------------------------------------------------------
    | RÔLES ET STATUT
    |--------------------------------------------------------------------------
    | Super admin et admin : toute la plateforme | Propriétaire : ses établissements | Client : ses réservations
    */

    /**
     * Indique si l'utilisateur possède l'un des rôles donnés.
     */
    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Indique si l'utilisateur a accès au back-office de la plateforme.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::SuperAdmin, UserRole::Admin);
    }

    /**
     * Coordonnées de reversement lisibles, ex. « Mobile Money · 07 00 00 00 00 (Awa Koné) ».
     */
    public function payoutAccountSummary(): ?string
    {
        if (! $this->payout_method || ! $this->payout_account) {
            return null;
        }

        return $this->payout_method->label().' · '.$this->payout_account.($this->payout_holder ? ' ('.$this->payout_holder.')' : '');
    }

    /**
     * Compte exempté d'abonnement (comptes de démonstration) : ni limite, ni facture, ni suspension.
     */
    public function isSubscriptionExempt(): bool
    {
        return (bool) $this->subscription_exempt;
    }

    /**
     * Téléphone lisible : « +225 07 01 23 45 67 ».
     */
    public function formattedPhone(): string
    {
        return PhoneNumber::format($this->indicatif_telephone, $this->phone);
    }

    /**
     * Téléphone au format international (liens tel:, WhatsApp, services de paiement) : « +2250701234567 ».
     */
    public function internationalPhone(): string
    {
        return PhoneNumber::e164($this->indicatif_telephone, $this->phone);
    }

    /**
     * Email « Mot de passe oublié » en français, aux couleurs de DS Holding.
     */
    #[Override]
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function isClient(): bool
    {
        return $this->role === UserRole::Client;
    }

    /**
     * Page d'accueil après connexion : l'espace client pour un client, l'administration pour les autres.
     */
    public function homeUrl(): string
    {
        return $this->isClient() ? route('client.dashboard') : route('dashboard');
    }

    /**
     * Client à qui il manque des informations utiles aux réservations (créé via Google / Facebook, par exemple).
     */
    public function needsProfileCompletion(): bool
    {
        return $this->isClient() && (blank($this->phone) || blank($this->city) || blank($this->country));
    }

    /**
     * Compte relié à Google ou Facebook.
     */
    public function usesSocialLogin(): bool
    {
        return filled($this->google_id) || filled($this->facebook_id);
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    public function isActive(): bool
    {
        return $this->statut === UserStatus::Active;
    }

    /**
     * Photo de profil, ou initiales sur fond doré à défaut.
     */
    public function avatarUrl(): string
    {
        if ($this->avatar_path) {
            return Storage::disk('public')->url($this->avatar_path);
        }

        if ($this->social_avatar) {
            return $this->social_avatar;
        }

        return 'https://ui-avatars.com/api/?background=d4a72c&color=0a1f44&bold=true&name='.urlencode($this->name);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    #[Scope]
    protected function withRole(Builder $query, UserRole $role): void
    {
        $query->where('role', $role);
    }

    /**
     * Administrateurs actifs (destinataires des alertes de la plateforme).
     */
    #[Scope]
    protected function backOffice(Builder $query): void
    {
        $query->whereIn('role', [UserRole::SuperAdmin, UserRole::Admin])->where('statut', UserStatus::Active);
    }
}
