<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The public incidents archive runs `MAX(updated_at)` as its cache-version stamp on every
     * request; without an index that is a full-table scan under the ×10 ЧС surge (ТЗ §13.1).
     */
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });
    }
};
