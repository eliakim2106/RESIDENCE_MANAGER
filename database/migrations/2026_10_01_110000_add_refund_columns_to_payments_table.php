<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remboursement partiel d'un paiement : montant déjà rendu au client et motif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('refunded_amount')->default(0)->after('amount')->comment('Montant rendu au client en XOF');
            $table->string('refund_reason', 500)->nullable()->after('refunded_at');
        });

        // Paiements déjà remboursés en totalité
        DB::table('payments')->where('statut', 'refunded')->update(['refunded_amount' => DB::raw('amount')]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['refunded_amount', 'refund_reason']);
        });
    }
};
