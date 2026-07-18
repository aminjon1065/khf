<?php

use App\Models\Subscriber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->string('push_token_hash', 64)->nullable()->unique()->after('token');
        });

        $pushTable = (string) config('webpush.table_name', 'push_subscriptions');
        $subscriberType = (new Subscriber)->getMorphClass();

        DB::table('subscribers')
            ->whereIn('id', DB::table($pushTable)
                ->select('subscribable_id')
                ->where('subscribable_type', $subscriberType))
            ->select(['id', 'token'])
            ->orderBy('id')
            ->chunkById(100, function (Collection $subscribers): void {
                foreach ($subscribers as $subscriber) {
                    DB::table('subscribers')
                        ->where('id', $subscriber->id)
                        ->update(['push_token_hash' => hash('sha256', $subscriber->token)]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropUnique(['push_token_hash']);
            $table->dropColumn('push_token_hash');
        });
    }
};
