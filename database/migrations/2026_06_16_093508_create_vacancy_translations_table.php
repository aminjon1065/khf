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
        Schema::create('vacancy_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->string('title');
            $table->string('slug');
            // Responsible subdivision / source of the publication (ТЗ §18, §20 «б»).
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('salary')->nullable();
            $table->string('summary', 500)->nullable();
            $table->longText('description')->nullable();
            // Qualification requirements for candidates (ТЗ §20 «н»).
            $table->longText('requirements')->nullable();
            $table->longText('responsibilities')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->timestamps();

            $table->unique(['vacancy_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacancy_translations');
    }
};
