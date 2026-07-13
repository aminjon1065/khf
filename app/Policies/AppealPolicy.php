<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Policies\Concerns\ChecksPiiPermissions;

class AppealPolicy
{
    use ChecksPiiPermissions;

    protected function viewPermission(): Permission
    {
        return Permission::ViewAppeals;
    }

    protected function managePermission(): Permission
    {
        return Permission::ManageAppeals;
    }
}
