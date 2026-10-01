<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Override;

/**
 * Avis client, notes sur 10 comme Booking.
 * Publié dès l'envoi ; le propriétaire peut répondre ou le signaler, un administrateur peut le masquer.
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
        'reported_at',
        'report_reason',
        'reported_by',
        'statut',
        'moderation_note',
        'moderated_at',
        'moderated_by',
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
            'reported_at' => 'datetime',
            'moderated_at' => 'datetime',
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

    /**
     * Propriétaire qui a signalé l'avis.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Administrateur qui a masqué ou republié l'avis.
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTHODES MÉTIER
    |--------------------------------------------------------------------------
    */

    public function isPublished(): bool
    {
        return $this->statut === ReviewStatus::Approved;
    }

    /**
     * Signalé par le propriétaire et pas encore traité par un administrateur.
     */
    public function isReported(): bool
    {
        return $this->reported_at !== null;
    }

    /**
     * Nom affiché publiquement : prénom et initiale (« Awa K. »).
     */
    public function authorName(): string
    {
        $name = trim((string) $this->user?->name);

        if ($name === '') {
            return 'Voyageur';
        }

        $last = Str::after($name, ' ');

        return Str::before($name, ' ').($last !== $name ? ' '.Str::upper(Str::substr($last, 0, 1)).'.' : '');
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

    /**
     * Avis des établissements d'un propriétaire.
     */
    #[Scope]
    protected function forOwner(Builder $query, User $owner): void
    {
        $query->whereHas('property', fn (Builder $query) => $query->where('owner_id', $owner->id));
    }
}
