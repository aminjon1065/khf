<?php

namespace App\Policies\Concerns;

use App\Enums\Permission;
use App\Models\User;

/**
 * Shared authorisation for admin PII / inbox modules (appeals, tourists, subscribers, …).
 *
 * View abilities accept either the dedicated `*.view` permission or `*.manage`.
 * Mutating abilities require `*.manage`.
 */
trait ChecksPiiPermissions
{
    abstract protected function viewPermission(): Permission;

    abstract protected function managePermission(): Permission;

    public function viewAny(User $user): bool
    {
        return $user->can($this->viewPermission()->value)
            || $user->can($this->managePermission()->value);
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, mixed $model): bool
    {
        return $user->can($this->managePermission()->value);
    }

    public function delete(User $user, mixed $model): bool
    {
        return $user->can($this->managePermission()->value);
    }

    public function export(User $user): bool
    {
        return $user->can($this->managePermission()->value);
    }

    public function download(User $user, mixed $model): bool
    {
        return $this->view($user, $model);
    }
}
