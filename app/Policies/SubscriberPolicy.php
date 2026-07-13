<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Policies\Concerns\ChecksPiiPermissions;

class SubscriberPolicy
{
    use ChecksPiiPermissions;

    protected function viewPermission(): Permission
    {
        return Permission::ViewSubscribers;
    }

    protected function managePermission(): Permission
    {
        return Permission::ManageSubscribers;
    }
}
