<?php

namespace App\Models;

use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\HasTranslations;
use App\Services\Cms\GlobalResolver;
use Illuminate\Database\Eloquent\Model;

/**
 * CMS global settings (Statamic globals analogue). {@see SiteGlobalTranslation} stores field data.
 *
 * @property int $id
 * @property string $handle
 * @property string $blueprint
 */
class SiteGlobal extends Model
{
    use ClearsResponseCache;
    use HasTranslations;

    protected $table = 'globals';

    /** @var class-string<SiteGlobalTranslation> */
    protected string $translationModel = SiteGlobalTranslation::class;

    protected string $translationForeignKey = 'global_id';

    /** @var list<string> */
    protected $fillable = [
        'handle',
        'blueprint',
    ];

    protected static function booted(): void
    {
        $forget = static function (SiteGlobal $global): void {
            app(GlobalResolver::class)->forget($global->handle);
        };

        static::saved($forget);
        static::deleted($forget);
    }

    public function getRouteKeyName(): string
    {
        return 'handle';
    }

    /**
     * Merged field data for a locale (from the translation row).
     *
     * @return array<string, mixed>
     */
    public function fieldData(?string $locale = null): array
    {
        $translation = $this->translation($locale);

        if ($translation === null) {
            return [];
        }

        /** @var array<string, mixed> $data */
        $data = $translation->data ?? [];

        return $data;
    }
}
