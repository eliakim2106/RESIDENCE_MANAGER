<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('nights');
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('cleaning_fee')->default(0);
            $table->unsignedInteger('service_fee')->default(0);
            $table->unsignedInteger('tax_amount')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('total_amount');
            $table->unsignedInteger('amount_paid')->default(0);
            $table->string('currency', 3)->default('XOF');
            $table->string('status', 20)->default('pending');
            $table->string('payment_state', 20)->default('unpaid');
            $table->string('cancellation_policy', 20);
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_phone', 30);
            $table->string('estimated_arrival_time', 20)->nullable();
            $table->text('special_requests')->nullable();
            $table->text('owner_notes')->nullable();
            $table->timestamp('expires_at')->nullable()->comment('Délai de paiement avant libération des unités');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'check_in', 'check_out']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('reservation_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('price_per_night')->comment('Prix moyen par nuit en XOF');
            $table->unsignedInteger('subtotal');
            $table->json('nightly_prices')->nullable()->comment('Détail du prix de chaque nuit');
            $table->timestamps();
        });

        Schema::create('reservation_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->boolean('is_child')->default(false);
            $table->string('id_document_number')->nullable();
            $table->string('nationality', 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_guests');
        Schema::dropIfExists('reservation_units');
        Schema::dropIfExists('reservations');
    }
};
