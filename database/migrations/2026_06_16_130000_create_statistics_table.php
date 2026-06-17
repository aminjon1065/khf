<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Official statistics / key activity indicators (ТЗ §20 подпункт «у»). The figure is stored as a
     * locale-independent string (e.g. "1 234", "98%"); the label/unit are per-locale.
     */
    public function up(): void
    {
        Schema::create('statistics', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('draft')->index();
            $table->string('value');
            $table->unsignedSmallInteger('year')->nullable()->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistics');
    }
};
