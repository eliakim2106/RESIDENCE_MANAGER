<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Part d'un paiement reversée au propriétaire (négative quand un remboursement vient la corriger).
 */
class PayoutItem extends Model
{
    protected $fillable = [
        'payout_id',
        'payment_id',
        'gross_amount',
        'commission_rate',
        'commission_amount',
        'amount',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'gross_amount' => 'integer',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'integer',
            'amount' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
