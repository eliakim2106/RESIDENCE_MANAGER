<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use App\Support\PhoneNumber;
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
        'indicatif_telephone',
        'subject',
        'message',
        'statut',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'statut' => ContactMessageStatus::class,
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

    /*
    |--------------------------------------------------------------------------
    | AFFICHAGE
    |--------------------------------------------------------------------------
    */

    public function formattedPhone(): ?string
    {
        return $this->phone ? PhoneNumber::format($this->indicatif_telephone, $this->phone) : null;
    }

    public function internationalPhone(): ?string
    {
        return $this->phone ? PhoneNumber::e164($this->indicatif_telephone, $this->phone) : null;
    }
}
