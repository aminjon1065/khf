<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A single alert must reach each subscriber at most once per channel (ТЗ §6.4.4 — защита от
     * повторной отправки). This unique key makes the fan-out idempotent: a queue retry after a
     * partial failure skips already-logged recipients instead of re-blasting the whole list.
     */
    public function up(): void
    {
        // Collapse any pre-existing duplicates (keeping the earliest row per recipient+channel)
        // before enforcing the key. Only fully-keyed rows are constrained — both MySQL and SQLite
        // permit repeated NULLs in a unique index, so null-keyed rows are left untouched.
        $keepIds = DB::table('notifications_log')
            ->selectRaw('MIN(id) as id')
            ->whereNotNull('alert_id')
            ->whereNotNull('subscriber_id')
            ->groupBy('alert_id', 'subscriber_id', 'channel')
            ->pluck('id');

        DB::table('notifications_log')
            ->whereNotNull('alert_id')
            ->whereNotNull('subscriber_id')
            ->whereNotIn('id', $keepIds)
            ->delete();

        Schema::table('notifications_log', function (Blueprint $table) {
            $table->unique(['alert_id', 'subscriber_id', 'channel'], 'notifications_log_recipient_channel_unique');
        });
    }

    public function down(): void
    {
        Schema::table('notifications_log', function (Blueprint $table) {
            $table->dropUnique('notifications_log_recipient_channel_unique');
        });
    }
};
