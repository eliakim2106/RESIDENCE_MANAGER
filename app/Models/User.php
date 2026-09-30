<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'city',
        'country',
        'role',
        'statut',
        'company_name',
        'avatar_path',
        'password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
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
}
