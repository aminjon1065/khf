<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seed the staff accounts (ТЗ §7, §8). Idempotent — re-running updates the existing users by email.
 * Privileged roles require confirmed 2FA (D-16 / EnsureTwoFactorEnabled), so these accounts log in
 * with e-mail + password and are guided through 2FA setup on first visit to the CMS.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'aminjon1065@gmail.com'],
            [
                'name' => 'Аминжон',
                'password' => Hash::make('password10'),
                'email_verified_at' => now(),
            ],
        );

        if (! $admin->hasRole(Role::SuperAdmin->value)) {
            $admin->assignRole(Role::SuperAdmin->value);
        }

        $moderator = User::updateOrCreate(
            ['email' => 'moderator@khf.test'],
            [
                'name' => 'Модератор',
                'password' => Hash::make('password10'),
                'email_verified_at' => now(),
            ],
        );

        if (! $moderator->hasRole(Role::Moderator->value)) {
            $moderator->assignRole(Role::Moderator->value);
        }
    }
}
