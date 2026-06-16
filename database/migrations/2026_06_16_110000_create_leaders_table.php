<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Leadership of the agency (ТЗ §20 подпункт «г»). Multilingual fields (name, position, bio,
     * citizen-reception schedule) live in `leader_translations`; the portrait is a media item.
     */
    public function up(): void
    {
        Schema::create('leaders', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('draft')->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaders');
    }
};
