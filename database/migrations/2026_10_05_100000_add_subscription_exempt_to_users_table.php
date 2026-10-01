<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Compte exempté d'abonnement : ni limite de formule, ni facture, ni suspension, établissements toujours en ligne.
 * Les comptes de démonstration en bénéficient.
 */
return new class extends Migration
{
    /**
     * Comptes de démonstration exemptés.
     */
    private const DEMO_ACCOUNTS = [
        'superadmin@dsholding.ci',
        'admin@dsholding.ci',
        'owner@dsholding.ci',
        'client@dsholding.ci',
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('subscription_exempt')->default(false)->after('payout_holder');
        });

        DB::table('users')->whereIn('email', self::DEMO_ACCOUNTS)->update(['subscription_exempt' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('subscription_exempt');
        });
    }
};
