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
        Schema::create('tourist_groups', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('leader_name');
            $table->string('leader_phone');
            $table->string('leader_email')->nullable();
            $table->unsignedSmallInteger('participants_count')->default(1);
            $table->text('route');
            $table->text('equipment')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->decimal('start_latitude', 10, 7)->nullable();
            $table->decimal('start_longitude', 10, 7)->nullable();
            $table->string('status')->default('new')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tourist_groups');
    }
};
