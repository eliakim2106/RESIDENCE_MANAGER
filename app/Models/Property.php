<?php

namespace App\Models;

use App\Enums\CancellationPolicy;
use App\Enums\PropertyStatus;
use App\Enums\ReviewStatus;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Établissement : hôtel, résidence meublée, appartement, villa...
 */
#[RouteKey('slug')]
#[Fillable([
    'owner_id', 'property_type_id', 'city_id', 'name', 'slug', 'short_description', 'description',
    'address', 'district', 'neighborhood', 'latitude', 'longitude', 'star_rating', 'manages_units', 'phone', 'email', 'website',
    'check_in_from', 'check_in_until', 'check_out_until', 'cancellation_policy', 'house_rules',
    'allows_pets', 'allows_smoking', 'allows_parties', 'logo_path', 'meta_title', 'meta_description', 'status', 'is_featured',
    'rating_average', 'reviews_count', 'published_at',
])]
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Property $property): void {
            $property->slug ??= static::uniqueSlug($property->name);
        });
    }

    /**
     * Slug unique dérivé du nom, ex. « residence-les-palmiers », puis « residence-les-palmiers-2 ».
     */
    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'etablissement';
        $slug = $base;

        for ($suffix = 2; static::withTrashed()->where('slug', $slug)->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PropertyStatus::class,
            'cancellation_policy' => CancellationPolicy::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'allows_pets' => 'boolean',
            'allows_smoking' => 'boolean',
            'allows_parties' => 'boolean',
            'is_featured' => 'boolean',
            'manages_units' => 'boolean',
            'rating_average' => 'decimal:1',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<PropertyType, $this>
     */
    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return HasMany<PropertyImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('position');
    }

    /**
     * @return HasOne<PropertyImage, $this>
     */
    public function coverImage(): HasOne
    {
        return $this->hasOne(PropertyImage::class)->ofMany(['is_cover' => 'max', 'id' => 'min']);
    }

    /**
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * @return BelongsToMany<Equipment, $this>
     */
    public function equipments(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class);
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', ReviewStatus::Approved);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    /**
     * URL publique du logo, null s'il n'y en a pas.
     *
     * @return Attribute<string|null, never>
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->logo_path === null ? null : (
            Str::startsWith($this->logo_path, ['http://', 'https://'])
                ? $this->logo_path
                : Storage::disk('public')->url($this->logo_path)
        ));
    }

    public function isPublished(): bool
    {
        return $this->status === PropertyStatus::Published;
    }

    /**
     * Recalcule la note moyenne et le nombre d'avis approuvés.
     */
    public function refreshRating(): void
    {
        $this->forceFill([
            'rating_average' => round((float) $this->approvedReviews()->avg('rating'), 1),
            'reviews_count' => $this->approvedReviews()->count(),
        ])->save();
    }

    /**
     * @param  Builder<Property>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', PropertyStatus::Published);
    }

    /**
     * @param  Builder<Property>  $query
     */
    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * @param  Builder<Property>  $query
     */
    #[Scope]
    protected function ownedBy(Builder $query, User $owner): void
    {
        $query->whereBelongsTo($owner, 'owner');
    }
}
