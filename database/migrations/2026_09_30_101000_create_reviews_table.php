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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->comment('Note globale sur 10');
            $table->unsignedTinyInteger('cleanliness')->nullable();
            $table->unsignedTinyInteger('comfort')->nullable();
            $table->unsignedTinyInteger('location')->nullable();
            $table->unsignedTinyInteger('staff')->nullable();
            $table->unsignedTinyInteger('value_for_money')->nullable();
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->text('owner_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['property_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
