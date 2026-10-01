<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rappel envoyé au propriétaire quelques jours avant l'échéance d'une facture (une seule fois).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('due_on');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
