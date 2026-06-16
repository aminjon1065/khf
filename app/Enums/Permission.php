<?php

namespace App\Enums;

/**
 * Granular CMS permissions (ТЗ §8). Roles are named sets of these (see App\Enums\Role); the access
 * model follows least privilege. Values are the strings stored by spatie/laravel-permission.
 */
enum Permission: string
{
    // Content
    case ViewPages = 'pages.view';
    case ManagePages = 'pages.manage';
    case ViewPosts = 'posts.view';
    case ManagePosts = 'posts.manage';
    case PublishPosts = 'posts.publish';
    case ManageCategories = 'categories.manage';
    case ViewDocuments = 'documents.view';
    case ManageDocuments = 'documents.manage';
    case ViewGuides = 'guides.view';
    case ManageGuides = 'guides.manage';
    case ViewVacancies = 'vacancies.view';
    case ManageVacancies = 'vacancies.manage';
    case ViewTenders = 'tenders.view';
    case ManageTenders = 'tenders.manage';
    case ViewLeadership = 'leadership.view';
    case ManageLeadership = 'leadership.manage';
    case ViewStructure = 'structure.view';
    case ManageStructure = 'structure.manage';
    case ManageMedia = 'media.manage';
    case ManageBlocks = 'blocks.manage';
    case ManageMenus = 'menus.manage';
    case ManageTranslations = 'translations.manage';

    // Emergencies, map & alerts
    case ViewIncidents = 'incidents.view';
    case ManageIncidents = 'incidents.manage';
    case ViewAlerts = 'alerts.view';
    case ManageAlerts = 'alerts.manage';
    case SendAlerts = 'alerts.send';
    case ManageMap = 'map.manage';

    // Services & personal data
    case ViewAppeals = 'appeals.view';
    case ManageAppeals = 'appeals.manage';
    case ViewTouristGroups = 'tourist-groups.view';
    case ManageTouristGroups = 'tourist-groups.manage';
    case ViewVacancyApplications = 'vacancy-applications.view';
    case ManageVacancyApplications = 'vacancy-applications.manage';
    case ViewTenderBids = 'tender-bids.view';
    case ManageTenderBids = 'tender-bids.manage';
    case ViewSubscribers = 'subscribers.view';
    case ManageSubscribers = 'subscribers.manage';

    // System
    case ViewUsers = 'users.view';
    case ManageUsers = 'users.manage';
    case ManageRoles = 'roles.manage';
    case ManageSettings = 'settings.manage';
    case ViewAudit = 'audit.view';
    case ViewAnalytics = 'analytics.view';

    /**
     * All permission string values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }
}
