<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Avis client, notes sur 10 comme Booking.
 */
class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'user_id',
        'property_id',
        'rating',
        'cleanliness',
        'comfort',
        'location',
        'staff',
        'value_for_money',
        'title',
        'comment',
        'owner_reply',
        'replied_at',
        'statut',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'cleanliness' => 'integer',
            'comfort' => 'integer',
            'location' => 'integer',
            'staff' => 'integer',
            'value_for_money' => 'integer',
            'replied_at' => 'datetime',
            'statut' => ReviewStatus::class,
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTHODES MÉTIER
    |--------------------------------------------------------------------------
    */

    /**
     * Appréciation textuelle de la note, à la manière de Booking.
     */
    public function ratingLabel(): string
    {
        return match (true) {
            $this->rating >= 9 => 'Exceptionnel',
            $this->rating >= 8 => 'Très bien',
            $this->rating >= 7 => 'Bien',
            $this->rating >= 6 => 'Agréable',
            default => 'Correct',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('statut', ReviewStatus::Approved);
    }
}
