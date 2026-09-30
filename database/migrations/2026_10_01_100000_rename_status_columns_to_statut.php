<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes de statut en français : « statut ».
 *
 * - Référentiels (villes, types, équipements) : le booléen is_active devient statut = actif / inactif.
 * - Unités : active / inactive deviennent actif / inactif.
 * - Autres tables : la colonne est renommée, les valeurs métier sont conservées (en attente, confirmée, publié…).
 */
return new class extends Migration
{
    /**
     * Tables dont la colonne status est simplement renommée.
     *
     * @var list<string>
     */
    private const RENAMED = ['users', 'properties', 'units', 'reservations', 'payments', 'maintenances', 'reviews', 'contact_messages'];

    /**
     * Tables dont le booléen is_active devient statut.
     *
     * @var list<string>
     */
    private const FLAGS = ['cities', 'property_types', 'unit_types', 'equipments'];

    public function up(): void
    {
        foreach (self::RENAMED as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->renameColumn('status', 'statut'));
        }

        DB::table('units')->where('statut', 'active')->update(['statut' => 'actif']);
        DB::table('units')->where('statut', 'inactive')->update(['statut' => 'inactif']);

        Schema::table('units', fn (Blueprint $table) => $table->string('statut', 20)->default('actif')->change());

        foreach (self::FLAGS as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->string('statut', 20)->default('actif')->after('is_active'));

            DB::table($name)->where('is_active', false)->update(['statut' => 'inactif']);

            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('is_active'));
        }
    }

    public function down(): void
    {
        foreach (self::FLAGS as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->boolean('is_active')->default(true)->after('statut'));

            DB::table($name)->where('statut', 'inactif')->update(['is_active' => false]);

            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('statut'));
        }

        Schema::table('units', fn (Blueprint $table) => $table->string('statut', 20)->default('active')->change());

        DB::table('units')->where('statut', 'actif')->update(['statut' => 'active']);
        DB::table('units')->where('statut', 'inactif')->update(['statut' => 'inactive']);

        foreach (self::RENAMED as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->renameColumn('statut', 'status'));
        }
    }
};
