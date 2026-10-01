<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * « J'aime » sur les établissements, ouverts à tous les visiteurs :
 * - un compte connecté aime une fois (user_id) ;
 * - un visiteur sans compte aime une fois par navigateur (visitor_id, identifiant gardé en cookie).
 * Le nombre de j'aime est recopié dans properties.likes_count pour l'affichage.
 * Les favoris existants des clients deviennent des j'aime.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('visitor_id')->nullable()->comment('Visiteur sans compte (cookie)');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['property_id', 'user_id']);
            $table->unique(['property_id', 'visitor_id']);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0)->after('reviews_count');
        });

        DB::table('property_likes')->insertUsing(
            ['property_id', 'user_id', 'created_at'],
            DB::table('favorites')->select('property_id', 'user_id', 'created_at'),
        );

        DB::table('properties')->update([
            'likes_count' => DB::raw('(SELECT COUNT(*) FROM property_likes WHERE property_likes.property_id = properties.id)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('likes_count');
        });

        Schema::dropIfExists('property_likes');
    }
};
