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
        'refunded_amount',
        'currency',
        'statut',
        'payment_url',
        'payment_token',
        'operator_reference',
        'provider_payload',
        'paid_at',
        'refunded_at',
        'refund_reason',
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
            'refunded_amount' => 'integer',
            'method' => PaymentMethod::class,
            'statut' => TransactionStatus::class,
            'provider_payload' => 'array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    /**
     * Les adresses de l'administration désignent un paiement par son identifiant de transaction.
     */
    #[Override]
    public function getRouteKeyName(): string
    {
        return 'transaction_id';
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

    /**
     * Montant conservé après remboursement éventuel.
     */
    public function netAmount(): int
    {
        return max(0, $this->amount - $this->refunded_amount);
    }

    /**
     * Montant encore remboursable (paiement encaissé uniquement).
     */
    public function refundableAmount(): int
    {
        return $this->isAccepted() ? $this->netAmount() : 0;
    }

    public function isPartiallyRefunded(): bool
    {
        return $this->isAccepted() && $this->refunded_amount > 0;
    }

    /**
     * Paiement enregistré à la main par l'établissement ou un administrateur (et non par CinetPay).
     */
    public function isManual(): bool
    {
        return $this->provider === 'manuel';
    }
}
