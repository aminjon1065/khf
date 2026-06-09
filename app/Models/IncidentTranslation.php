<?php

namespace App\Models;

use Database\Factories\IncidentTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-locale title and description of an {@see Incident} (ТЗ §9).
 *
 * @property int $id
 * @property int $incident_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 */
class IncidentTranslation extends Model
{
    /** @use HasFactory<IncidentTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'incident_id',
        'locale',
        'title',
        'description',
    ];

    /**
     * @return BelongsTo<Incident, $this>
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
