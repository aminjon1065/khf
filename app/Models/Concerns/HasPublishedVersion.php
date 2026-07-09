<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use App\Services\Cms\PublishedVersionService;
use Illuminate\Database\Eloquent\Model;

/**
 * Working copy vs published version (Statamic staging analogue).
 *
 * @phpstan-require-extends Model
 */
trait HasPublishedVersion
{
    public function hasPublishedSnapshot(): bool
    {
        return is_array($this->published_snapshot) && $this->published_snapshot !== [];
    }

    public function hasUnpublishedChanges(): bool
    {
        if ($this->status !== ContentStatus::Published || ! $this->hasPublishedSnapshot()) {
            return false;
        }

        return app(PublishedVersionService::class)->hasUnpublishedChanges($this);
    }

    public function capturePublishedSnapshot(): void
    {
        app(PublishedVersionService::class)->capture($this);
    }
}
