<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Surcharge du calendrier pour une nuit : prix spécifique, fermeture ou blocage d'unités.
 */
class Availability extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'date',
        'price',
        'blocked_quantity',
        'is_closed',
        'min_nights',
        'note',
    ];

    #[Override]
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
