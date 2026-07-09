<?php

namespace App\Models;

use App\Support\RedirectResolver;
use Database\Factories\RedirectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    /** @use HasFactory<RedirectFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'from_path',
        'to_url',
        'status_code',
        'is_active',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Boot the model to invalidate the redirect map cache.
     */
    protected static function booted(): void
    {
        static::saved(fn (): mixed => RedirectResolver::clearCache());
        static::deleted(fn (): mixed => RedirectResolver::clearCache());
    }
}
