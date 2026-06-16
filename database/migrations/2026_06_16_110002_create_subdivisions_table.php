<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Structural subdivisions of the agency (ТЗ §20 подпункт «б»). Self-referencing `parent_id`
     * builds the hierarchy (≥3 levels, §83); multilingual fields live in `subdivision_translations`.
     */
    public function up(): void
    {
        Schema::create('subdivisions', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('draft')->index();
            $table->foreignId('parent_id')->nullable()->constrained('subdivisions')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            // Staff headcount (ТЗ §20 «б» — численность работников).
            $table->unsignedInteger('staff_count')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subdivisions');
    }
};
