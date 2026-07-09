<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $media_file_id
 * @property string $usable_type
 * @property int $usable_id
 * @property string $context
 * @property string $label
 */
class MediaUsage extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'media_file_id',
        'usable_type',
        'usable_id',
        'context',
        'label',
    ];

    /**
     * @return BelongsTo<MediaFile, $this>
     */
    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function usable(): MorphTo
    {
        return $this->morphTo();
    }
}
