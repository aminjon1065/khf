<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gov_service_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gov_service_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->string('title');
            $table->string('slug');
            $table->string('summary')->nullable();
            $table->longText('description')->nullable();
            $table->longText('eligibility')->nullable();
            $table->longText('required_documents')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->timestamps();

            $table->unique(['gov_service_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gov_service_translations');
    }
};
