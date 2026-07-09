<?php

namespace App\Models;

use App\Services\Cms\GlobalResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-locale values for a {@see SiteGlobal} entry.
 *
 * @property int $id
 * @property int $global_id
 * @property string $locale
 * @property array<string, mixed> $data
 */
class SiteGlobalTranslation extends Model
{
    protected $table = 'global_translations';

    /** @var list<string> */
    protected $fillable = [
        'global_id',
        'locale',
        'data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (SiteGlobalTranslation $translation): void {
            $global = $translation->global;

            if ($global !== null) {
                app(GlobalResolver::class)->forget($global->handle);
            }
        });
    }

    /**
     * @return BelongsTo<SiteGlobal, $this>
     */
    public function global(): BelongsTo
    {
        return $this->belongsTo(SiteGlobal::class, 'global_id');
    }
}
