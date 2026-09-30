<?php

namespace App\Models;

use Database\Factories\UnitRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tarif saisonnier appliqué à une unité sur une période.
 */
#[Fillable(['unit_id', 'name', 'starts_on', 'ends_on', 'price', 'min_nights'])]
class UnitRate extends Model
{
    /** @use HasFactory<UnitRateFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'price' => 'integer',
            'min_nights' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
