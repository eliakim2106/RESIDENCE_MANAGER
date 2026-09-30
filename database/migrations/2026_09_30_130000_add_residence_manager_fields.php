<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Champs saisis par les formulaires de Residence Manager qui n'existaient pas dans le schéma de base :
 * ville et pays de l'utilisateur, quartier, SEO et gestion des unités de l'établissement, prix promotionnel de l'unité.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('city')->nullable()->after('phone');
            $table->string('country')->nullable()->after('city');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->string('neighborhood')->nullable()->after('district');
            $table->boolean('manages_units')->default(true)->after('star_rating');
            $table->string('meta_title', 60)->nullable()->after('logo_path');
            $table->string('meta_description', 160)->nullable()->after('meta_title');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->unsignedInteger('promo_price')->nullable()->after('base_price')->comment('Prix promotionnel par nuit en XOF');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('promo_price');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['neighborhood', 'manages_units', 'meta_title', 'meta_description']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['city', 'country']);
        });
    }
};
