<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MySQL FULLTEXT indexes for site search (ТЗ §10). Skipped on SQLite (tests use LIKE fallback).
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $indexes = [
            ['post_translations', 'post_translations_search_fulltext', 'title, excerpt, body'],
            ['page_translations', 'page_translations_search_fulltext', 'title, content'],
            ['guide_translations', 'guide_translations_search_fulltext', 'title, summary, content'],
            ['document_translations', 'document_translations_search_fulltext', 'name, description'],
            ['vacancy_translations', 'vacancy_translations_search_fulltext', 'title, summary, description, requirements, responsibilities'],
            ['tender_translations', 'tender_translations_search_fulltext', 'title, summary, description, requirements, terms'],
            ['leader_translations', 'leader_translations_search_fulltext', 'full_name, position, bio'],
            ['subdivision_translations', 'subdivision_translations_search_fulltext', 'name, functions'],
            ['gallery_translations', 'gallery_translations_search_fulltext', 'title, description'],
            ['faq_translations', 'faq_translations_search_fulltext', 'question, answer'],
            ['statistic_translations', 'statistic_translations_search_fulltext', 'label'],
        ];

        foreach ($indexes as [$table, $indexName, $columns]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $exists = DB::selectOne(
                'SELECT COUNT(*) AS total FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
                [$table, $indexName],
            );

            if ((int) ($exists->total ?? 0) > 0) {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` ADD FULLTEXT `{$indexName}` ({$columns})");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $indexes = [
            ['post_translations', 'post_translations_search_fulltext'],
            ['page_translations', 'page_translations_search_fulltext'],
            ['guide_translations', 'guide_translations_search_fulltext'],
            ['document_translations', 'document_translations_search_fulltext'],
            ['vacancy_translations', 'vacancy_translations_search_fulltext'],
            ['tender_translations', 'tender_translations_search_fulltext'],
            ['leader_translations', 'leader_translations_search_fulltext'],
            ['subdivision_translations', 'subdivision_translations_search_fulltext'],
            ['gallery_translations', 'gallery_translations_search_fulltext'],
            ['faq_translations', 'faq_translations_search_fulltext'],
            ['statistic_translations', 'statistic_translations_search_fulltext'],
        ];

        foreach ($indexes as [$table, $indexName]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        }
    }
};
