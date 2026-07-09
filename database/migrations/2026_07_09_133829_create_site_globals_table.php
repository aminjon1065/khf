<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('global_id')->constrained('globals')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->json('data');
            $table->timestamps();

            $table->unique(['global_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_translations');
    }
};
