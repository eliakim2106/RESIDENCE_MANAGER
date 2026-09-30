<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avis client, notes sur 10 comme Booking.
 */
#[Fillable([
    'reservation_id', 'user_id', 'property_id', 'rating', 'cleanliness', 'comfort', 'location',
    'staff', 'value_for_money', 'title', 'comment', 'owner_reply', 'replied_at', 'status',
])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
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
            'status' => ReviewStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

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

    /**
     * @param  Builder<Review>  $query
     */
    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('status', ReviewStatus::Approved);
    }
}
