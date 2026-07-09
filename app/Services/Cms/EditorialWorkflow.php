<?php

namespace App\Services\Cms;

use App\Enums\ContentStatus;
use App\Enums\Permission;
use App\Models\User;
use App\Support\PublicationScheduler;

/**
 * Role-aware publication workflow: editors submit for moderation, publishers approve.
 */
class EditorialWorkflow
{
    /**
     * @return list<ContentStatus>
     */
    public function allowedTransitions(ContentStatus $current, ?User $user = null): array
    {
        if ($this->canPublish($user)) {
            return $current->allowedTransitions();
        }

        return match ($current) {
            ContentStatus::Draft => [ContentStatus::Moderation],
            ContentStatus::Moderation => [ContentStatus::Draft],
            ContentStatus::Published, ContentStatus::Archived => [],
        };
    }

    public function canTransition(?User $user, ContentStatus $from, ContentStatus $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to, $this->allowedTransitions($from, $user), true);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function transitionOptions(ContentStatus $current, ?User $user = null): array
    {
        return array_map(
            fn (ContentStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            $this->allowedTransitions($current, $user),
        );
    }

    public function canPublish(?User $user = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $user->can(Permission::PublishPosts->value)
            || $user->can(Permission::PublishPages->value)
            || $user->can(Permission::PublishContent->value);
    }

    public function canViewModerationQueue(?User $user = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->canPublish($user)
            || $user->can(Permission::ViewModeration->value);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalizePublication(array $data): array
    {
        return PublicationScheduler::normalize($data);
    }
}
