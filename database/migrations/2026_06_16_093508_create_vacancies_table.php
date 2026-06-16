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
        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('draft')->index();
            $table->string('employment_type')->default('full_time')->index();
            $table->unsignedSmallInteger('positions_count')->default(1);
            $table->timestamp('published_at')->nullable()->index();
            // Application deadline — civil-service questionnaire submission cut-off (ТЗ §20 «н»).
            $table->date('deadline_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacancies');
    }
};
