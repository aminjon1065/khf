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
        Schema::table('media_files', function (Blueprint $table) {
            $table->foreignId('media_folder_id')
                ->nullable()
                ->after('user_id')
                ->constrained('media_folders')
                ->nullOnDelete();
            $table->decimal('focal_x', 5, 2)->default(50)->after('alt_text');
            $table->decimal('focal_y', 5, 2)->default(50)->after('focal_x');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_folder_id');
            $table->dropColumn(['focal_x', 'focal_y']);
        });
    }
};
