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
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            // Official procurement number assigned by the agency (ТЗ §20 «э»).
            $table->string('tender_number')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->string('type')->default('goods')->index();
            $table->decimal('budget', 15, 2)->nullable();
            $table->unsignedSmallInteger('lots_count')->default(1);
            $table->timestamp('published_at')->nullable()->index();
            // Bid submission deadline (ТЗ §9 — «торговая площадка»).
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
        Schema::dropIfExists('tenders');
    }
};
