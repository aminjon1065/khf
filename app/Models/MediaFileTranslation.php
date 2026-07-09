<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $media_file_id
 * @property string $locale
 * @property string|null $alt_text
 */
class MediaFileTranslation extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'media_file_id',
        'locale',
        'alt_text',
    ];

    /**
     * @return BelongsTo<MediaFile, $this>
     */
    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }
}
