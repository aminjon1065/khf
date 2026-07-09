<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 */
class MediaTag extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
    ];

    /**
     * @return BelongsToMany<MediaFile, $this>
     */
    public function mediaFiles(): BelongsToMany
    {
        return $this->belongsToMany(MediaFile::class);
    }

    public static function normalizeName(string $name): string
    {
        return Str::lower(trim($name));
    }
}
