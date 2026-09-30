<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Validation des établissements : date de soumission, décision de l'administrateur et motif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('status')->comment('Dernière soumission à validation');
            $table->text('moderation_note')->nullable()->after('submitted_at')->comment('Motif du refus ou de la suspension');
            $table->timestamp('moderated_at')->nullable()->after('moderation_note');
            $table->foreignId('moderated_by')->nullable()->after('moderated_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn(['submitted_at', 'moderation_note', 'moderated_at']);
        });
    }
};
