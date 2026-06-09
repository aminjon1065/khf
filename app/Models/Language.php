<?php

namespace App\Models;

use Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * A portal interface/content language (ТЗ §14). The table is the canonical, CMS-editable source
 * of supported locales; `config('app.locales')` is only a static fallback for early boot.
 *
 * @property int $id
 * @property string $code internal locale code (tj, ru, en)
 * @property string $name admin-facing name
 * @property string $native_name name in its own language
 * @property string $hreflang valid BCP-47 tag for SEO output
 * @property string $direction ltr|rtl
 * @property bool $is_active
 * @property bool $is_default
 * @property int $sort_order
 */
class Language extends Model
{
    /** @use HasFactory<LanguageFactory> */
    use HasFactory;

    public const CACHE_KEY = 'languages.active';

    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'hreflang',
        'direction',
        'is_active',
        'is_default',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $forget = static fn () => Cache::forget(self::CACHE_KEY);

        static::saved($forget);
        static::deleted($forget);
    }

    /**
     * Active languages, ordered for display. Cached and invalidated on save/delete.
     *
     * @return Collection<int, Language>
     */
    public static function active(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, static fn (): Collection => static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get());
    }

    /**
     * Active locale codes in display order.
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return static::active()->pluck('code')->all();
    }

    /**
     * The default language, falling back to the first active one.
     */
    public static function default(): ?Language
    {
        $active = static::active();

        return $active->firstWhere('is_default', true) ?? $active->first();
    }

    /**
     * The default locale code, falling back to the configured fallback locale.
     */
    public static function defaultCode(): string
    {
        return static::default()?->code ?? config('app.fallback_locale');
    }

    /**
     * Whether the given code is an active portal locale.
     */
    public static function isSupported(string $code): bool
    {
        return in_array($code, static::codes(), true);
    }
}
