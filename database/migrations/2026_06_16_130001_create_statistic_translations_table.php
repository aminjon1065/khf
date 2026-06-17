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
        Schema::create('statistic_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statistic_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->string('label');
            $table->string('unit')->nullable();
            $table->timestamps();

            $table->unique(['statistic_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistic_translations');
    }
};
