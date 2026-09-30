<?php

namespace App\Models;

use App\Enums\MaintenanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Période de maintenance qui rend des unités indisponibles.
 */
class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'title',
        'description',
        'starts_on',
        'ends_on',
        'quantity',
        'statut',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'quantity' => 'integer',
            'statut' => MaintenanceStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
