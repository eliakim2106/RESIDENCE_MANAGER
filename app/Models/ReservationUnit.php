<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Ligne de réservation : une unité (et sa quantité) réservée sur la période.
 */
class ReservationUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'unit_id',
        'quantity',
        'price_per_night',
        'subtotal',
        'nightly_prices',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price_per_night' => 'integer',
            'subtotal' => 'integer',
            'nightly_prices' => 'array',
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

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class)->withTrashed();
    }
}
