<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrégateur qui a ouvert le paiement en ligne d'une facture (cinetpay, fedapay) et identifiant de la transaction chez lui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->string('gateway', 20)->nullable()->after('transaction_id');
            $table->string('gateway_reference', 100)->nullable()->after('gateway');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'gateway_reference']);
        });
    }
};
