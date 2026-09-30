<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Transaction de paiement (CinetPay ou encaissement sur place).
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'user_id',
        'transaction_id',
        'provider',
        'method',
        'operator',
        'amount',
        'currency',
        'statut',
        'payment_url',
        'payment_token',
        'operator_reference',
        'provider_payload',
        'paid_at',
        'refunded_at',
    ];

    protected $hidden = [
        'payment_token',
        'provider_payload',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'method' => PaymentMethod::class,
            'statut' => TransactionStatus::class,
            'provider_payload' => 'array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTHODES MÉTIER
    |--------------------------------------------------------------------------
    */

    public function isAccepted(): bool
    {
        return $this->statut === TransactionStatus::Accepted;
    }
}
