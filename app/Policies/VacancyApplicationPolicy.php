<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Policies\Concerns\ChecksPiiPermissions;

class VacancyApplicationPolicy
{
    use ChecksPiiPermissions;

    protected function viewPermission(): Permission
    {
        return Permission::ViewVacancyApplications;
    }

    protected function managePermission(): Permission
    {
        return Permission::ManageVacancyApplications;
    }
}
