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
        Schema::create('subdivision_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subdivision_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->string('name');
            // Head of the subdivision — name + position (ТЗ §20 «б»).
            $table->string('head')->nullable();
            // Tasks and functions (ТЗ §20 «б» — сведения о задачах и функциях).
            $table->longText('functions')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();

            $table->unique(['subdivision_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subdivision_translations');
    }
};
