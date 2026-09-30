<?php

namespace App\Models;

use Database\Factories\ReservationUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne de réservation : une unité (et sa quantité) réservée sur la période.
 */
#[Fillable(['reservation_id', 'unit_id', 'quantity', 'price_per_night', 'subtotal', 'nightly_prices'])]
class ReservationUnit extends Model
{
    /** @use HasFactory<ReservationUnitFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price_per_night' => 'integer',
            'subtotal' => 'integer',
            'nightly_prices' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class)->withTrashed();
    }
}
