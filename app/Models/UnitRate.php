<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Tarif saisonnier appliqué à une unité sur une période.
 */
class UnitRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'name',
        'starts_on',
        'ends_on',
        'price',
        'min_nights',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'price' => 'integer',
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
