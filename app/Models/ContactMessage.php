<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'status' => ContactMessageStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Établissement concerné, si le message a été envoyé depuis sa fiche.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
