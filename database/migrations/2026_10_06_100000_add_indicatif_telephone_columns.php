<?php

use App\Support\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indicatif téléphonique séparé du numéro (comptes, établissements, voyageur d'une réservation).
 * Les numéros existants (« +225 07 01 02 03 04 », « 0701020304 »…) sont répartis entre les deux colonnes ;
 * le numéro est ensuite enregistré sans espaces ni indicatif.
 */
return new class extends Migration
{
    /**
     * Table => colonne du numéro.
     */
    private const COLUMNS = [
        'users' => 'phone',
        'properties' => 'phone',
        'reservations' => 'guest_phone',
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $phone) {
            Schema::table($table, function (Blueprint $blueprint) use ($phone) {
                $blueprint->string('indicatif_telephone', 5)->default('+225')->after($phone);
            });

            DB::table($table)->whereNotNull($phone)->where($phone, '!=', '')->orderBy('id')
                ->each(function (object $row) use ($table, $phone): void {
                    [$dial, $number] = PhoneNumber::split($row->{$phone});

                    DB::table($table)->where('id', $row->id)->update(['indicatif_telephone' => $dial, $phone => $number]);
                });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $phone) {
            DB::table($table)->whereNotNull($phone)->where($phone, '!=', '')->orderBy('id')
                ->each(function (object $row) use ($table, $phone): void {
                    DB::table($table)->where('id', $row->id)->update([$phone => PhoneNumber::format($row->indicatif_telephone, $row->{$phone})]);
                });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('indicatif_telephone');
            });
        }
    }
};
