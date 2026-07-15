<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encrypted values are much longer than their plaintext, so the varchar(255) `email`/`phone`
     * columns would truncate the ciphertext and break decryption. Widen them to TEXT (ТЗ §12.5).
     * `message` is already TEXT.
     */
    public function up(): void
    {
        Schema::table('appeals', function (Blueprint $table) {
            $table->text('email')->change();
            $table->text('phone')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('appeals', function (Blueprint $table) {
            $table->string('email')->change();
            $table->string('phone')->nullable()->change();
        });
    }
};
