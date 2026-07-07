<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Publication window for content with {@see published_at} / {@see unpublished_at} (ТЗ §6.2).
 */
trait ScopesPublicationWindow
{
    /**
     * Visible on the public site: status published, publish time reached, not yet unpublished.
     *
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ContentStatus::Published)
            ->where(function (Builder $inner) {
                $inner->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function (Builder $inner) {
                $inner->whereNull('unpublished_at')->orWhere('unpublished_at', '>', now());
            });
    }

    public function isScheduledForFuture(): bool
    {
        return $this->status === ContentStatus::Published
            && $this->published_at !== null
            && $this->published_at->isFuture();
    }

    public function isScheduledForUnpublish(): bool
    {
        return $this->status === ContentStatus::Published
            && $this->unpublished_at !== null
            && $this->unpublished_at->isFuture();
    }
}
