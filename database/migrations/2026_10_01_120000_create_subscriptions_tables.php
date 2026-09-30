<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Abonnements des propriétaires : formules, abonnement en cours de chaque propriétaire, factures.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Formules proposées par DS Holding (prix réglés par le super administrateur)
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('monthly_price')->comment('Prix mensuel en XOF');
            $table->unsignedInteger('yearly_price')->nullable()->comment('Prix annuel en XOF (facultatif)');
            $table->unsignedSmallInteger('max_properties')->nullable()->comment('Vide : illimité');
            $table->unsignedSmallInteger('max_units')->nullable()->comment('Vide : illimité');
            $table->unsignedSmallInteger('trial_days')->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0)->comment('Commission sur les réservations, en %');
            $table->json('features')->nullable()->comment('Avantages affichés au propriétaire');
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('statut', 20)->default('actif');
            $table->timestamps();
        });

        // Abonnement d'un propriétaire : le plus récent est l'abonnement en cours
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->restrictOnDelete();
            $table->string('billing_cycle', 20)->default('monthly');
            $table->string('statut', 20)->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->date('current_period_start')->nullable();
            $table->date('current_period_end')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'statut']);
        });

        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan_name', 100)->comment('Formule au moment de la facture');
            $table->string('billing_cycle', 20);
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('XOF');
            $table->string('statut', 20)->default('unpaid');
            $table->date('due_on');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['statut', 'due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
