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
        Schema::create('leader_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leader_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->string('full_name');
            $table->string('position');
            $table->longText('bio')->nullable();
            // Citizen reception schedule (ТЗ §20 «г» — график приёма граждан).
            $table->text('reception')->nullable();
            $table->timestamps();

            $table->unique(['leader_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leader_translations');
    }
};
