<?php

use App\Models\Language;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media_file_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_file_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('alt_text')->nullable();
            $table->timestamps();

            $table->unique(['media_file_id', 'locale']);
        });

        $defaultLocale = Language::query()->where('is_default', true)->value('code')
            ?? config('app.locale');

        $now = now();

        DB::table('media_files')
            ->whereNotNull('alt_text')
            ->where('alt_text', '!=', '')
            ->orderBy('id')
            ->each(function (object $mediaFile) use ($defaultLocale, $now): void {
                DB::table('media_file_translations')->insert([
                    'media_file_id' => $mediaFile->id,
                    'locale' => $defaultLocale,
                    'alt_text' => $mediaFile->alt_text,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_file_translations');
    }
};
