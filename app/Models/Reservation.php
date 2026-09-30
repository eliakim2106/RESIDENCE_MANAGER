<?php

namespace App\Models;

use App\Enums\CancellationPolicy;
use App\Enums\PaymentState;
use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Override;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'user_id',
        'property_id',
        'check_in',
        'check_out',
        'nights',
        'adults',
        'children',
        'subtotal',
        'cleaning_fee',
        'service_fee',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'currency',
        'statut',
        'payment_state',
        'cancellation_policy',
        'guest_name',
        'guest_email',
        'guest_phone',
        'estimated_arrival_time',
        'special_requests',
        'owner_notes',
        'expires_at',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'nights' => 'integer',
            'adults' => 'integer',
            'children' => 'integer',
            'subtotal' => 'integer',
            'cleaning_fee' => 'integer',
            'service_fee' => 'integer',
            'tax_amount' => 'integer',
            'discount_amount' => 'integer',
            'total_amount' => 'integer',
            'amount_paid' => 'integer',
            'statut' => ReservationStatus::class,
            'payment_state' => PaymentState::class,
            'cancellation_policy' => CancellationPolicy::class,
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    #[Override]
    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    protected static function booted(): void
    {
        static::creating(function (Reservation $reservation): void {
            $reservation->reference ??= static::generateReference();
        });
    }

    /**
     * Génère une référence unique lisible, ex. DS-7K2M9QXA.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'DS-'.Str::upper(Str::random(8));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReservationUnit::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(ReservationGuest::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTHODES MÉTIER
    |--------------------------------------------------------------------------
    */

    public function balanceDue(): int
    {
        return max(0, $this->total_amount - $this->amount_paid);
    }

    /**
     * Date limite d'annulation gratuite selon la politique de l'établissement (null = non remboursable).
     */
    public function freeCancellationDeadline(): ?Carbon
    {
        $hours = $this->cancellation_policy->freeCancellationHours();

        return $hours === null ? null : $this->check_in->copy()->subHours($hours);
    }

    public function isCancellable(): bool
    {
        return in_array($this->statut, [ReservationStatus::Pending, ReservationStatus::Confirmed], true)
            && $this->check_in->isFuture();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Réservations qui chevauchent la période [from, to[ (les dates de départ sont libres).
     */
    #[Scope]
    protected function overlapping(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->where('check_in', '<', $to->copy()->startOfDay())
            ->where('check_out', '>', $from->copy()->startOfDay());
    }

    /**
     * Réservations qui occupent réellement des unités (en attente non expirée, confirmée ou terminée).
     */
    #[Scope]
    protected function blocking(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->whereIn('statut', [ReservationStatus::Confirmed, ReservationStatus::Completed])
                ->orWhere(function (Builder $query): void {
                    $query->where('statut', ReservationStatus::Pending)
                        ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
                });
        });
    }
}
