<?php

namespace App\Models;

use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\HasTranslations;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A content tag for posts and documents (ТЗ §6.2, §6.8). Name and slug are per-locale in
 * `tag_translations`.
 *
 * @property int $id
 */
class Tag extends Model
{
    use ClearsResponseCache;

    /** @use HasFactory<TagFactory> */
    use HasFactory;

    use HasTranslations;
    use LogsActivity;

    /**
     * @return BelongsToMany<Page, $this>
     */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class);
    }

    /**
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    /**
     * @return BelongsToMany<Document, $this>
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
