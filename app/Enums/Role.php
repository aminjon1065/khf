<?php

namespace App\Enums;

/**
 * CMS roles (ТЗ §8). Super-administrator, moderator (operations), publisher (approve & publish),
 * and editor (draft / moderation only). Permissions stay granular via {@see Permission}.
 */
enum Role: string
{
    case SuperAdmin = 'super-admin';
    case Moderator = 'moderator';
    case Publisher = 'publisher';
    case Editor = 'editor';

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
     * Pipe-delimited role list for the admin route middleware.
     */
    public static function adminMiddleware(): string
    {
        return implode('|', self::values());
    }

    /**
     * Privileged CMS roles that must use two-factor authentication (ТЗ §7.1, §12.3).
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
        return self::values();
    }

    /**
     * Permissions granted to this role.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => Permission::cases(),

            self::Moderator => [
                ...self::editorialPermissions(publish: true),
                ...self::operationsPermissions(),
                Permission::ViewAudit,
                Permission::ViewAnalytics,
            ],

            self::Publisher => [
                ...self::editorialPermissions(publish: true),
                Permission::ViewAudit,
            ],

            self::Editor => self::editorialPermissions(publish: false),
        };
    }

    /**
     * @return list<Permission>
     */
    private static function editorialPermissions(bool $publish): array
    {
        $permissions = [
            Permission::ViewPages,
            Permission::ManagePages,
            Permission::ViewPosts,
            Permission::ManagePosts,
            Permission::ManageCategories,
            Permission::ManageTags,
            Permission::ViewDocuments,
            Permission::ManageDocuments,
            Permission::ViewGuides,
            Permission::ManageGuides,
            Permission::ViewVacancies,
            Permission::ManageVacancies,
            Permission::ViewTenders,
            Permission::ManageTenders,
            Permission::ViewLeadership,
            Permission::ManageLeadership,
            Permission::ViewStructure,
            Permission::ManageStructure,
            Permission::ViewGallery,
            Permission::ManageGallery,
            Permission::ViewFaqs,
            Permission::ManageFaqs,
            Permission::ViewPolls,
            Permission::ManagePolls,
            Permission::ViewServices,
            Permission::ManageServices,
            Permission::ViewStatistics,
            Permission::ManageStatistics,
            Permission::ManageMedia,
            Permission::ManageBlocks,
            Permission::ManageMenus,
            Permission::ManageTranslations,
        ];

        if ($publish) {
            $permissions[] = Permission::PublishPosts;
            $permissions[] = Permission::PublishPages;
            $permissions[] = Permission::PublishContent;
            $permissions[] = Permission::ViewModeration;
        }

        return $permissions;
    }

    /**
     * @return list<Permission>
     */
    private static function operationsPermissions(): array
    {
        return [
            Permission::ViewIncidents,
            Permission::ManageIncidents,
            Permission::ViewAlerts,
            Permission::ManageAlerts,
            Permission::SendAlerts,
            Permission::ManageMap,
            Permission::ViewAppeals,
            Permission::ManageAppeals,
            Permission::ViewTouristGroups,
            Permission::ManageTouristGroups,
            Permission::ViewVacancyApplications,
            Permission::ManageVacancyApplications,
            Permission::ViewTenderBids,
            Permission::ManageTenderBids,
            Permission::ViewSubscribers,
            Permission::ManageSubscribers,
        ];
    }
}
