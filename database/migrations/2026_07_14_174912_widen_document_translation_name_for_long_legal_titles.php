<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Real legal-act titles (e.g. full postanovlenie names) routinely exceed 255 characters, so the
     * document title column must be TEXT rather than varchar to hold them without truncation
     * (ТЗ §6.8). The `(document_id, locale)` unique key is unaffected.
     */
    public function up(): void
    {
        Schema::table('document_translations', function (Blueprint $table) {
            $table->text('name')->change();
        });
    }

    public function down(): void
    {
        Schema::table('document_translations', function (Blueprint $table) {
            $table->string('name')->change();
        });
    }
};
