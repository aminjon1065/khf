<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Safety guides / memos catalogued by hazard type and audience (ТЗ §6.5). Multilingual fields live
 * in `guide_translations`; downloadable attachments are stored on the private disk via medialibrary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table): void {
            $table->id();
            $table->string('hazard_type')->nullable()->index(); // IncidentType value, or null for general
            $table->string('audience')->default('general')->index(); // GuideAudience: general | children
            $table->string('status')->default('draft')->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};
