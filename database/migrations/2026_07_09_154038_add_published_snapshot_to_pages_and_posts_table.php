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
        Schema::table('pages', function (Blueprint $table) {
            $table->json('published_snapshot')->nullable()->after('is_home');
            $table->timestamp('published_snapshot_at')->nullable()->after('published_snapshot');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->json('published_snapshot')->nullable()->after('unpublished_at');
            $table->timestamp('published_snapshot_at')->nullable()->after('published_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['published_snapshot', 'published_snapshot_at']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['published_snapshot', 'published_snapshot_at']);
        });
    }
};
