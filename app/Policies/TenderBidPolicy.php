<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Policies\Concerns\ChecksPiiPermissions;

class TenderBidPolicy
{
    use ChecksPiiPermissions;

    protected function viewPermission(): Permission
    {
        return Permission::ViewTenderBids;
    }

    protected function managePermission(): Permission
    {
        return Permission::ManageTenderBids;
    }
}
