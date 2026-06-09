<?php

namespace App\Models;

use Database\Factories\DocumentTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-locale name and description of a {@see Document} (ТЗ §9).
 *
 * @property int $id
 * @property int $document_id
 * @property string $locale
 * @property string $name
 * @property string|null $description
 */
class DocumentTranslation extends Model
{
    /** @use HasFactory<DocumentTranslationFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'document_id',
        'locale',
        'name',
        'description',
    ];

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
