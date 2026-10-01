<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Connexion avec Google ou Facebook : identifiant du compte chez le fournisseur et photo de profil fournie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id', 64)->nullable()->unique()->after('remember_token');
            $table->string('facebook_id', 64)->nullable()->unique()->after('google_id');
            $table->string('social_avatar', 500)->nullable()->after('avatar_path')->comment('Photo fournie par Google ou Facebook');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropUnique(['facebook_id']);
            $table->dropColumn(['google_id', 'facebook_id', 'social_avatar']);
        });
    }
};
