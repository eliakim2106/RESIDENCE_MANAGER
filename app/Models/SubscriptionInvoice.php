<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Facture d'abonnement d'un propriétaire (une par période).
 */
class SubscriptionInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'subscription_id',
        'user_id',
        'plan_name',
        'billing_cycle',
        'period_start',
        'period_end',
        'amount',
        'currency',
        'statut',
        'due_on',
        'paid_at',
        'payment_method',
        'payment_reference',
        'transaction_id',
        'gateway',
        'gateway_reference',
        'recorded_by',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'amount' => 'integer',
            'statut' => InvoiceStatus::class,
            'due_on' => 'date',
            'paid_at' => 'datetime',
            'payment_method' => PaymentMethod::class,
        ];
    }

    #[Override]
    public function getRouteKeyName(): string
    {
        return 'number';
    }

    protected static function booted(): void
    {
        static::creating(function (SubscriptionInvoice $invoice): void {
            $invoice->number ??= static::nextNumber();
        });
    }

    /**
     * Numéro suivant de l'année, ex. « ABO-2026-00042 ».
     */
    public static function nextNumber(): string
    {
        $prefix = 'ABO-'.now()->format('Y').'-';
        $last = static::where('number', 'like', $prefix.'%')->max('number');
        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTHODES MÉTIER
    |--------------------------------------------------------------------------
    */

    public function isUnpaid(): bool
    {
        return $this->statut === InvoiceStatus::Unpaid;
    }

    /**
     * Échéance dépassée et facture toujours impayée.
     */
    public function isOverdue(): bool
    {
        return $this->isUnpaid() && $this->due_on->isPast() && ! $this->due_on->isToday();
    }
}
