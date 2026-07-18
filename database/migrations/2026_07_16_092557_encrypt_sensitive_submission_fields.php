<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, list<string>> */
    private const SENSITIVE_COLUMNS = [
        'appeals' => ['email', 'phone', 'message', 'internal_note'],
        'vacancy_applications' => ['email', 'phone', 'cover_letter', 'internal_note'],
        'tender_bids' => ['contact_name', 'email', 'phone', 'proposal', 'internal_note'],
        'tourist_groups' => [
            'leader_phone',
            'leader_email',
            'route',
            'equipment',
            'start_latitude',
            'start_longitude',
            'internal_note',
        ],
    ];

    /**
     * Encrypt non-searchable personal and commercial data already stored in the database.
     */
    public function up(): void
    {
        Schema::table('vacancy_applications', function (Blueprint $table) {
            $table->text('email')->change();
            $table->text('phone')->nullable()->change();
        });

        Schema::table('tender_bids', function (Blueprint $table) {
            $table->text('contact_name')->change();
            $table->text('email')->change();
            $table->text('phone')->nullable()->change();
        });

        Schema::table('tourist_groups', function (Blueprint $table) {
            $table->text('leader_phone')->change();
            $table->text('leader_email')->nullable()->change();
            $table->text('start_latitude')->nullable()->change();
            $table->text('start_longitude')->nullable()->change();
        });

        $this->transformSensitiveColumns($this->encryptIfPlaintext(...));
    }

    /**
     * Restore plaintext values before returning columns to their original types.
     */
    public function down(): void
    {
        $this->transformSensitiveColumns($this->decryptIfEncrypted(...));

        Schema::table('vacancy_applications', function (Blueprint $table) {
            $table->string('email')->change();
            $table->string('phone')->nullable()->change();
        });

        Schema::table('tender_bids', function (Blueprint $table) {
            $table->string('contact_name')->change();
            $table->string('email')->change();
            $table->string('phone')->nullable()->change();
        });

        Schema::table('tourist_groups', function (Blueprint $table) {
            $table->string('leader_phone')->change();
            $table->string('leader_email')->nullable()->change();
            $table->decimal('start_latitude', 10, 7)->nullable()->change();
            $table->decimal('start_longitude', 10, 7)->nullable()->change();
        });
    }

    /**
     * @param  callable(string): string  $transform
     */
    private function transformSensitiveColumns(callable $transform): void
    {
        foreach (self::SENSITIVE_COLUMNS as $table => $columns) {
            DB::table($table)
                ->select(['id', ...$columns])
                ->orderBy('id')
                ->chunkById(100, function (Collection $rows) use ($table, $columns, $transform): void {
                    foreach ($rows as $row) {
                        $updates = [];

                        foreach ($columns as $column) {
                            $value = $row->{$column};

                            if ($value !== null) {
                                $updates[$column] = $transform((string) $value);
                            }
                        }

                        if ($updates !== []) {
                            DB::table($table)->where('id', $row->id)->update($updates);
                        }
                    }
                });
        }
    }

    private function encryptIfPlaintext(string $value): string
    {
        try {
            Crypt::decryptString($value);

            return $value;
        } catch (DecryptException) {
            return Crypt::encryptString($value);
        }
    }

    private function decryptIfEncrypted(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }
};
