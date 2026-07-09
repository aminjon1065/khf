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
        Schema::create('media_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('media_file_media_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['media_file_id', 'media_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_file_media_tag');
        Schema::dropIfExists('media_tags');
    }
};
