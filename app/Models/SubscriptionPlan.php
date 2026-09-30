<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\BillingCycle;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Override;

/**
 * Formule d'abonnement proposée aux propriétaires (prix et limites réglés par le super administrateur).
 */
class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'yearly_price',
        'max_properties',
        'max_units',
        'trial_days',
        'commission_rate',
        'features',
        'is_featured',
        'position',
        'statut',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'monthly_price' => 'integer',
            'yearly_price' => 'integer',
            'max_properties' => 'integer',
            'max_units' => 'integer',
            'trial_days' => 'integer',
            'commission_rate' => 'decimal:2',
            'features' => 'array',
            'is_featured' => 'boolean',
            'position' => 'integer',
            'statut' => ActiveStatus::class,
        ];
    }

    #[Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saving(function (SubscriptionPlan $plan): void {
            if (blank($plan->slug) || $plan->isDirty('name')) {
                $base = Str::slug($plan->name) ?: 'formule';
                $slug = $base;

                for ($suffix = 2; static::where('slug', $slug)->whereKeyNot($plan->id)->exists(); $suffix++) {
                    $slug = "{$base}-{$suffix}";
                }

                $plan->slug = $slug;
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTHODES MÉTIER
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->statut === ActiveStatus::Active;
    }

    /**
     * Prix d'une période ; le prix annuel vaut à défaut douze mois.
     */
    public function priceFor(BillingCycle $cycle): int
    {
        return $cycle === BillingCycle::Yearly
            ? ($this->yearly_price ?? $this->monthly_price * 12)
            : $this->monthly_price;
    }

    public function offersYearly(): bool
    {
        return $this->yearly_price !== null;
    }

    /**
     * Économie du paiement annuel, en mois offerts (ex. 2).
     */
    public function yearlySavingMonths(): int
    {
        if (! $this->yearly_price || $this->monthly_price <= 0) {
            return 0;
        }

        return max(0, (int) floor(($this->monthly_price * 12 - $this->yearly_price) / $this->monthly_price));
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('statut', ActiveStatus::Active);
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('monthly_price');
    }
}
