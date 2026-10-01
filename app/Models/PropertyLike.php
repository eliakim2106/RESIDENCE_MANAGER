<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * « J'aime » d'un visiteur sur un établissement : par compte (user_id) ou, sans compte, par navigateur (visitor_id).
 */
class PropertyLike extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'property_id',
        'user_id',
        'visitor_id',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
