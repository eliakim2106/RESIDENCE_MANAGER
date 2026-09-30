<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('price')->nullable()->comment('Prix spécifique de la nuit en XOF');
            $table->unsignedSmallInteger('blocked_quantity')->default(0)->comment('Unités bloquées manuellement');
            $table->boolean('is_closed')->default(false);
            $table->unsignedSmallInteger('min_nights')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['unit_id', 'date']);
        });

        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('status', 20)->default('planned');
            $table->timestamps();

            $table->index(['unit_id', 'starts_on', 'ends_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
        Schema::dropIfExists('availabilities');
    }
};
