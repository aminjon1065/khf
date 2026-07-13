<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Policies\Concerns\ChecksPiiPermissions;

class TouristGroupPolicy
{
    use ChecksPiiPermissions;

    protected function viewPermission(): Permission
    {
        return Permission::ViewTouristGroups;
    }

    protected function managePermission(): Permission
    {
        return Permission::ManageTouristGroups;
    }
}
