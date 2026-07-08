<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Government services catalogue (ТЗ §20 «ф» — каталог государственных услуг).
     */
    public function up(): void
    {
        Schema::create('gov_services', function (Blueprint $table) {
            $table->id();
            $table->string('category')->index();
            $table->string('status')->default('draft')->index();
            $table->boolean('is_online')->default(false);
            $table->string('external_url')->nullable();
            $table->string('processing_time')->nullable();
            $table->string('fee')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gov_services');
    }
};
