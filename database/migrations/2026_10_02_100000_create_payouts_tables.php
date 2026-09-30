<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reversements aux propriétaires : DS Holding encaisse les réservations en ligne puis reverse
 * à chaque propriétaire sa part (encaissé − remboursements − commission de sa formule).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Où le propriétaire souhaite recevoir ses reversements
        Schema::table('users', function (Blueprint $table) {
            $table->string('payout_method', 30)->nullable()->after('company_name');
            $table->string('payout_account', 100)->nullable()->after('payout_method')->comment('Numéro Mobile Money ou RIB/IBAN');
            $table->string('payout_holder', 191)->nullable()->after('payout_account')->comment('Titulaire du compte');
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('gross_amount')->comment('Encaissé net des remboursements, en XOF');
            $table->unsignedInteger('commission_amount')->default(0);
            $table->unsignedInteger('amount')->comment('Montant reversé au propriétaire');
            $table->string('currency', 3)->default('XOF');
            $table->string('method', 30);
            $table->string('reference', 100)->nullable();
            $table->string('account', 255)->nullable()->comment('Coordonnées de reversement au moment du virement');
            $table->string('notes', 500)->nullable();
            $table->string('statut', 20)->default('paid');
            $table->timestamp('paid_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'statut']);
        });

        // Détail d'un reversement : ce qui a été reversé pour chaque paiement (négatif pour un remboursement ultérieur)
        Schema::create('payout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->integer('gross_amount');
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->integer('commission_amount')->default(0);
            $table->integer('amount');
            $table->timestamps();

            $table->index(['payment_id', 'payout_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_items');
        Schema::dropIfExists('payouts');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['payout_method', 'payout_account', 'payout_holder']);
        });
    }
};
