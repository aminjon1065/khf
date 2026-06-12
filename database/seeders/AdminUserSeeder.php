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

        // Fictitious staff exist only to populate the CMS user list and role filters during
        // development — never seed them into production alongside the real super-admin account.
        if (! app()->isProduction()) {
            $this->seedDemoStaff();
        }
    }

    /**
     * Additional demo moderators for local/staging. Idempotent: accounts already present (matched by
     * e-mail) are left as-is and only re-checked for their role. The mix of confirmed/absent 2FA gives
     * the user list realistic "2FA enabled" vs "setup pending" states.
     */
    private function seedDemoStaff(): void
    {
        /** @var list<array{name: string, email: string, two_factor: bool}> $staff */
        $staff = [
            ['name' => 'Гулнора Раҳимова', 'email' => 'gulnora.rahimova@khf.test', 'two_factor' => true],
            ['name' => 'Фирӯз Назаров', 'email' => 'firuz.nazarov@khf.test', 'two_factor' => true],
            ['name' => 'Сабоҳат Шарифова', 'email' => 'sabohat.sharifova@khf.test', 'two_factor' => true],
            ['name' => 'Далер Қодиров', 'email' => 'daler.qodirov@khf.test', 'two_factor' => false],
            ['name' => 'Нигина Сафарова', 'email' => 'nigina.safarova@khf.test', 'two_factor' => false],
        ];

        foreach ($staff as $person) {
            $user = User::query()->where('email', $person['email'])->first();

            if ($user === null) {
                $factory = User::factory();

                if ($person['two_factor']) {
                    $factory = $factory->withTwoFactor();
                }

                $user = $factory->create([
                    'name' => $person['name'],
                    'email' => $person['email'],
                    'password' => Hash::make('password10'),
                ]);
            }

            if (! $user->hasRole(Role::Moderator->value)) {
                $user->assignRole(Role::Moderator->value);
            }
        }
    }
}
