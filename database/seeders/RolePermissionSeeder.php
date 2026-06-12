<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the RBAC permissions and roles (ТЗ §8). Idempotent — safe to re-run; existing roles are
     * re-synced to the canonical permission set.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        // Reset the registrar again: findOrCreate() above primed its in-memory cache with the
        // pre-existing (empty) permission set and Spatie does not refresh it on insert, so without
        // this syncPermissions() below would not see the permissions we just created.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Role::cases() as $role) {
            $model = RoleModel::findOrCreate($role->value, 'web');

            $model->syncPermissions(
                array_map(fn (Permission $permission): string => $permission->value, $role->permissions()),
            );
        }
    }
}
