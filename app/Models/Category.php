<?php

namespace App\Models;

use App\Models\Concerns\ClearsResponseCache;
use App\Models\Concerns\HasTranslations;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A content category / rubric (ТЗ §6.2, Приложение Б). Name and slug are per-locale in
 * `category_translations`.
 *
 * @property int $id
 * @property int $sort_order
 */
class Category extends Model
{
    use ClearsResponseCache;

    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    use HasTranslations;
    use LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
