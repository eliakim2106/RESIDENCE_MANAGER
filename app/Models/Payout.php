<?php

namespace App\Models;

use App\Enums\PayoutMethod;
use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Reversement de DS Holding à un propriétaire : la part qui lui revient sur les paiements encaissés.
 */
class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'user_id',
        'gross_amount',
        'commission_amount',
        'amount',
        'currency',
        'method',
        'reference',
        'account',
        'notes',
        'statut',
        'paid_at',
        'cancelled_at',
        'recorded_by',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'gross_amount' => 'integer',
            'commission_amount' => 'integer',
            'amount' => 'integer',
            'method' => PayoutMethod::class,
            'statut' => PayoutStatus::class,
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    #[Override]
    public function getRouteKeyName(): string
    {
        return 'number';
    }

    protected static function booted(): void
    {
        static::creating(function (Payout $payout): void {
            $payout->number ??= static::nextNumber();
        });
    }

    /**
     * Numéro suivant de l'année, ex. « REV-2026-00042 ».
     */
    public static function nextNumber(): string
    {
        $prefix = 'REV-'.now()->format('Y').'-';
        $last = static::where('number', 'like', $prefix.'%')->max('number');
        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
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

    public function items(): HasMany
    {
        return $this->hasMany(PayoutItem::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTHODES MÉTIER
    |--------------------------------------------------------------------------
    */

    public function isPaid(): bool
    {
        return $this->statut === PayoutStatus::Paid;
    }
}
