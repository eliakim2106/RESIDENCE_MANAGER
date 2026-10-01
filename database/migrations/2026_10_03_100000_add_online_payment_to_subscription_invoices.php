<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paiement en ligne (CinetPay) des factures d'abonnement : dernière transaction ouverte par le propriétaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->string('transaction_id', 64)
                ->nullable()
                ->unique()
                ->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->dropUnique(['transaction_id']);
            $table->dropColumn('transaction_id');
        });
    }
};
