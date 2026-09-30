<?php

namespace App\Models;

use App\Enums\MaintenanceStatus;
use Database\Factories\MaintenanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Période de maintenance qui rend des unités indisponibles.
 */
#[Fillable(['unit_id', 'title', 'description', 'starts_on', 'ends_on', 'quantity', 'status'])]
class Maintenance extends Model
{
    /** @use HasFactory<MaintenanceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'quantity' => 'integer',
            'status' => MaintenanceStatus::class,
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
