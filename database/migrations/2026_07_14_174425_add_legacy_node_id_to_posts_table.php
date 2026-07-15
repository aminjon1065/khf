<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records the Drupal node id each post was migrated from (khf.tj/kchs.tj `/node/{id}`), so the
     * legacy 301-redirect map can point every old article URL at its new slug (ТЗ §15.1, WP-3).
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_node_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['legacy_node_id']);
            $table->dropColumn('legacy_node_id');
        });
    }
};
