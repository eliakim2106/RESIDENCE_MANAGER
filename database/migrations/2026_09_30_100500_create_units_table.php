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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_type_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('max_adults')->default(2);
            $table->unsignedTinyInteger('max_children')->default(0);
            $table->unsignedTinyInteger('bedrooms')->default(1);
            $table->unsignedTinyInteger('beds')->default(1);
            $table->unsignedTinyInteger('bathrooms')->default(1);
            $table->unsignedSmallInteger('size_m2')->nullable();
            $table->unsignedSmallInteger('quantity')->default(1)->comment('Nombre d’unités identiques');
            $table->unsignedInteger('base_price')->comment('Prix par nuit en XOF');
            $table->unsignedInteger('weekend_price')->nullable()->comment('Prix des nuits de vendredi et samedi en XOF');
            $table->unsignedInteger('cleaning_fee')->default(0);
            $table->unsignedSmallInteger('min_nights')->default(1);
            $table->unsignedSmallInteger('max_nights')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['property_id', 'slug']);
        });

        Schema::create('unit_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('caption')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('unit_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedInteger('price')->comment('Prix par nuit en XOF sur la période');
            $table->unsignedSmallInteger('min_nights')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'starts_on', 'ends_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_rates');
        Schema::dropIfExists('unit_images');
        Schema::dropIfExists('units');
    }
};
