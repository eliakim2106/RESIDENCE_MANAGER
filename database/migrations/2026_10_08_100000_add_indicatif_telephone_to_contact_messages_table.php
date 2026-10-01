<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indicatif du pays du numéro laissé dans le formulaire de contact, comme pour les autres formulaires.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('indicatif_telephone', 5)->default('+225')->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn('indicatif_telephone');
        });
    }
};
