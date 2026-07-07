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
        foreach (['posts', 'vacancies', 'tenders'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->timestamp('unpublished_at')->nullable()->index()->after('published_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['posts', 'vacancies', 'tenders'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('unpublished_at');
            });
        }
    }
};
