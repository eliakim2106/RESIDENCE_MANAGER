<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class ReservationGuest extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'full_name',
        'is_child',
        'id_document_number',
        'nationality',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'is_child' => 'boolean',
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
}
