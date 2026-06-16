<?php

namespace App\Enums;

/**
 * CMS roles. The full ТЗ §8 set (6 roles) is reduced to two for now per the project owner:
 * Super-administrator (full access) and Moderator (day-to-day content + operations). Additional
 * roles can be created at runtime via the CMS later; permissions stay granular so that split is easy.
 */
enum Role: string
{
    case SuperAdmin = 'super-admin';
    case Moderator = 'moderator';

    /**
     * Russian display label for the CMS (ТЗ §7.1 — Russian interface).
     */
    public function label(): string
    {
        return __('enums.role.'.$this->value);
    }

    /**
     * All role string values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }

    /**
     * Both roles are privileged, so both must use two-factor authentication (ТЗ §7.1, §12.3).
     */
    public function requiresTwoFactor(): bool
    {
        return true;
    }

    /**
     * Role values that mandate two-factor authentication.
     *
     * @return list<string>
     */
    public static function twoFactorRequired(): array
    {
        return array_values(array_map(
            fn (self $role): string => $role->value,
            array_filter(self::cases(), fn (self $role): bool => $role->requiresTwoFactor()),
        ));
    }

    /**
     * Permissions granted to this role. Super-admin implicitly has everything (also enforced via a
     * Gate::before in AppServiceProvider), so it is granted all permissions explicitly too. The
     * moderator gets all content and emergency-operations permissions, but not user, role or
     * system-settings management — those stay with the super-admin (least privilege, §8).
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => Permission::cases(),

            self::Moderator => [
                // Content
                Permission::ViewPages, Permission::ManagePages,
                Permission::ViewPosts, Permission::ManagePosts, Permission::PublishPosts,
                Permission::ManageCategories,
                Permission::ViewDocuments, Permission::ManageDocuments,
                Permission::ViewGuides, Permission::ManageGuides,
                Permission::ViewVacancies, Permission::ManageVacancies,
                Permission::ViewTenders, Permission::ManageTenders,
                Permission::ViewLeadership, Permission::ManageLeadership,
                Permission::ViewStructure, Permission::ManageStructure,
                Permission::ManageMedia, Permission::ManageBlocks, Permission::ManageMenus,
                Permission::ManageTranslations,
                // Emergencies, map & alerts
                Permission::ViewIncidents, Permission::ManageIncidents,
                Permission::ViewAlerts, Permission::ManageAlerts, Permission::SendAlerts,
                Permission::ManageMap,
                // Services & personal data
                Permission::ViewAppeals, Permission::ManageAppeals,
                Permission::ViewTouristGroups, Permission::ManageTouristGroups,
                Permission::ViewVacancyApplications, Permission::ManageVacancyApplications,
                Permission::ViewTenderBids, Permission::ManageTenderBids,
                Permission::ViewSubscribers, Permission::ManageSubscribers,
                // Read-only system insight
                Permission::ViewAudit, Permission::ViewAnalytics,
            ],
        };
    }
}
