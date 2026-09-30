<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;

/**
 * Abonnement d'un propriétaire à une formule.
 */
class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'billing_cycle',
        'statut',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'suspended_at',
        'cancelled_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'statut' => SubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'date',
            'current_period_end' => 'date',
            'suspended_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function openInvoice(): HasOne
    {
        return $this->hasOne(SubscriptionInvoice::class)->where('statut', InvoiceStatus::Unpaid)->oldestOfMany('due_on');
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTHODES MÉTIER
    |--------------------------------------------------------------------------
    */

    public function isInGoodStanding(): bool
    {
        return $this->statut->isInGoodStanding();
    }

    public function onTrial(): bool
    {
        return $this->statut === SubscriptionStatus::Trial;
    }

    /**
     * Jours d'essai restants (0 hors essai).
     */
    public function trialDaysLeft(): int
    {
        return $this->onTrial() && $this->trial_ends_at
            ? max(0, (int) ceil(now()->diffInDays($this->trial_ends_at, false)))
            : 0;
    }
}
