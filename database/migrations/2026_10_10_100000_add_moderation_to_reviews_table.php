<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modération des avis : signalement par le propriétaire, masquage motivé par un administrateur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('reported_at')->nullable()->after('replied_at');
            $table->text('report_reason')->nullable()->after('reported_at');
            $table->foreignId('reported_by')->nullable()->after('report_reason')->constrained('users')->nullOnDelete();
            $table->text('moderation_note')->nullable()->after('statut');
            $table->timestamp('moderated_at')->nullable()->after('moderation_note');
            $table->foreignId('moderated_by')->nullable()->after('moderated_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reported_by');
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn(['reported_at', 'report_reason', 'moderation_note', 'moderated_at']);
        });
    }
};
