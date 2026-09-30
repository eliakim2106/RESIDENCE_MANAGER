<?php

namespace App\Models;

use Database\Factories\AvailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Surcharge du calendrier pour une nuit : prix spécifique, fermeture ou blocage d'unités.
 */
#[Fillable(['unit_id', 'date', 'price', 'blocked_quantity', 'is_closed', 'min_nights', 'note'])]
class Availability extends Model
{
    /** @use HasFactory<AvailabilityFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'price' => 'integer',
            'blocked_quantity' => 'integer',
            'is_closed' => 'boolean',
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
