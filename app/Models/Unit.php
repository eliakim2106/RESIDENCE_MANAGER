<?php

namespace App\Models;

use App\Enums\UnitStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Override;

/**
 * Unité réservable : chambre, studio, appartement, suite...
 * Le champ quantity permet de gérer plusieurs unités identiques (ex. 20 chambres doubles).
 */
class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id',
        'unit_type_id',
        'name',
        'slug',
        'description',
        'max_adults',
        'max_children',
        'bedrooms',
        'beds',
        'bathrooms',
        'size_m2',
        'quantity',
        'base_price',
        'promo_price',
        'weekend_price',
        'cleaning_fee',
        'min_nights',
        'max_nights',
        'status',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'status' => UnitStatus::class,
            'max_adults' => 'integer',
            'max_children' => 'integer',
            'quantity' => 'integer',
            'base_price' => 'integer',
            'promo_price' => 'integer',
            'weekend_price' => 'integer',
            'cleaning_fee' => 'integer',
            'min_nights' => 'integer',
            'max_nights' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Unit $unit): void {
            if ($unit->slug !== null) {
                return;
            }

            $base = Str::slug($unit->name) ?: 'unite';
            $unit->slug = $base;

            for ($suffix = 2; static::withTrashed()->where('property_id', $unit->property_id)->where('slug', $unit->slug)->exists(); $suffix++) {
                $unit->slug = "{$base}-{$suffix}";
            }
        });
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

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(UnitImage::class)->orderBy('position');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(UnitRate::class);
    }

    public function equipments(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    public function reservationUnits(): HasMany
    {
        return $this->hasMany(ReservationUnit::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSEURS
    |--------------------------------------------------------------------------
    */

    /**
     * Capacité totale (adultes + enfants).
     */
    protected function capacity(): Attribute
    {
        return Attribute::get(fn (): int => $this->max_adults + $this->max_children);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', UnitStatus::Active);
    }
}
