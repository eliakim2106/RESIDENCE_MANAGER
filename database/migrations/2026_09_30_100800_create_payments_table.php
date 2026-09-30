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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transaction_id', 64)->unique();
            $table->string('provider', 20)->default('cinetpay');
            $table->string('method', 20)->nullable();
            $table->string('operator', 30)->nullable()->comment('OM, MOMO, FLOOZ, WAVE, VISA...');
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('XOF');
            $table->string('status', 20)->default('pending');
            $table->text('payment_url')->nullable();
            $table->string('payment_token')->nullable();
            $table->string('operator_reference')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
