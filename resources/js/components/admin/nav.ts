import {
    ArrowRightLeft,
    BarChart3,
    Bell,
    BookOpen,
    Briefcase,
    ClipboardCheck,
    ClipboardList,
    Contact,
    FileSignature,
    FileText,
    FolderTree,
    Globe,
    Gavel,
    HelpCircle,
    Image,
    Inbox,
    Languages,
    LayoutDashboard,
    ListChecks,
    ListTree,
    LayoutGrid,
    Layers,
    Mail,
    Mountain,
    Network,
    Newspaper,
    ScrollText,
    Tags,
    TriangleAlert,
    Users,
} from 'lucide-react';
import { dashboard as adminDashboard } from '@/routes/admin';
import { hub as contentHub } from '@/routes/admin/content';
import { index as alertsIndex } from '@/routes/admin/alerts';
import { index as appealsIndex } from '@/routes/admin/appeals';
import { index as auditLogsIndex } from '@/routes/admin/audit-logs';
import { index as categoriesIndex } from '@/routes/admin/categories';
import { index as documentsIndex } from '@/routes/admin/documents';
import { index as faqsIndex } from '@/routes/admin/faqs';
import { index as galleryIndex } from '@/routes/admin/gallery';
import { index as blueprintsIndex } from '@/routes/admin/blueprints';
import { index as globalsIndex } from '@/routes/admin/globals';
import { index as guidesIndex } from '@/routes/admin/guides';
import { index as incidentsIndex } from '@/routes/admin/incidents';
import { index as languagesIndex } from '@/routes/admin/languages';
import { index as leadershipIndex } from '@/routes/admin/leadership';
import { index as mediaIndex } from '@/routes/admin/media';
import { index as menusIndex } from '@/routes/admin/menus';
import { index as moderationIndex } from '@/routes/admin/moderation';
import { index as pagesIndex } from '@/routes/admin/pages';
import { index as pollsIndex } from '@/routes/admin/polls';
import { index as postsIndex } from '@/routes/admin/posts';
import { index as redirectsIndex } from '@/routes/admin/redirects';
import { index as servicesIndex } from '@/routes/admin/services';
import { index as statisticsIndex } from '@/routes/admin/statistics';
import { index as structureIndex } from '@/routes/admin/structure';
import { index as tagsIndex } from '@/routes/admin/tags';
import { index as subscribersIndex } from '@/routes/admin/subscribers';
import { index as tenderBidsIndex } from '@/routes/admin/tender-bids';
import { index as tendersIndex } from '@/routes/admin/tenders';
import { index as touristGroupsIndex } from '@/routes/admin/tourist-groups';
import { index as usersIndex } from '@/routes/admin/users';
import { index as vacanciesIndex } from '@/routes/admin/vacancies';
import { index as vacancyApplicationsIndex } from '@/routes/admin/vacancy-applications';
import type { NavItem } from '@/types';

export type AdminNavItem = NavItem & { permission?: string };

export type AdminNavGroup = {
    label: string;
    items: AdminNavItem[];
};

/**
 * CMS navigation (Statamic-style grouped sections), shared by the sidebar and the command palette.
 * Items can be gated by a permission string (super-admin passes everything).
 */
export const navGroups: AdminNavGroup[] = [
    {
        label: 'Обзор',
        items: [
            {
                title: 'Панель управления',
                href: adminDashboard(),
                icon: LayoutDashboard,
            },
        ],
    },
    {
        label: 'Об организации',
        items: [
            {
                title: 'Руководство',
                href: leadershipIndex(),
                icon: Contact,
                permission: 'leadership.manage',
            },
            {
                title: 'Структура',
                href: structureIndex(),
                icon: Network,
                permission: 'structure.manage',
            },
        ],
    },
    {
        label: 'Контент',
        items: [
            {
                title: 'Все коллекции',
                href: contentHub(),
                icon: LayoutGrid,
            },
            {
                title: 'Модерация',
                href: moderationIndex(),
                icon: ClipboardCheck,
                permission: 'moderation.view',
            },
            {
                title: 'Новости',
                href: postsIndex(),
                icon: Newspaper,
                permission: 'posts.manage',
            },
            {
                title: 'Страницы',
                href: pagesIndex(),
                icon: FileText,
                permission: 'pages.manage',
            },
            {
                title: 'Рубрики',
                href: categoriesIndex(),
                icon: FolderTree,
                permission: 'categories.manage',
            },
            {
                title: 'Теги',
                href: tagsIndex(),
                icon: Tags,
                permission: 'tags.manage',
            },
            { title: 'Медиабиблиотека', href: mediaIndex(), icon: Image },
            {
                title: 'Меню',
                href: menusIndex(),
                icon: ListTree,
                permission: 'menus.manage',
            },
            {
                title: 'Документы',
                href: documentsIndex(),
                icon: FileText,
                permission: 'documents.manage',
            },
            {
                title: 'Памятки',
                href: guidesIndex(),
                icon: BookOpen,
                permission: 'guides.manage',
            },
            {
                title: 'Вакансии',
                href: vacanciesIndex(),
                icon: Briefcase,
                permission: 'vacancies.manage',
            },
            {
                title: 'Тендеры',
                href: tendersIndex(),
                icon: Gavel,
                permission: 'tenders.manage',
            },
            {
                title: 'Фотогалерея',
                href: galleryIndex(),
                icon: Image,
                permission: 'gallery.manage',
            },
            {
                title: 'Вопросы и ответы',
                href: faqsIndex(),
                icon: HelpCircle,
                permission: 'faqs.manage',
            },
            {
                title: 'Опросы',
                href: pollsIndex(),
                icon: ListChecks,
                permission: 'polls.manage',
            },
            {
                title: 'Услуги',
                href: servicesIndex(),
                icon: ClipboardList,
                permission: 'services.manage',
            },
            {
                title: 'Статистика',
                href: statisticsIndex(),
                icon: BarChart3,
                permission: 'statistics.manage',
            },
        ],
    },
    {
        label: 'Чрезвычайные ситуации',
        items: [
            {
                title: 'События ЧС',
                href: incidentsIndex(),
                icon: TriangleAlert,
                permission: 'incidents.manage',
            },
            {
                title: 'Оповещения',
                href: alertsIndex(),
                icon: Bell,
                permission: 'alerts.manage',
            },
        ],
    },
    {
        label: 'Сервисы',
        items: [
            {
                title: 'Обращения',
                href: appealsIndex(),
                icon: Inbox,
                permission: 'appeals.manage',
            },
            {
                title: 'Тургруппы',
                href: touristGroupsIndex(),
                icon: Mountain,
                permission: 'tourist-groups.manage',
            },
            {
                title: 'Заявки на вакансии',
                href: vacancyApplicationsIndex(),
                icon: ClipboardList,
                permission: 'vacancy-applications.manage',
            },
            {
                title: 'Заявки на тендеры',
                href: tenderBidsIndex(),
                icon: FileSignature,
                permission: 'tender-bids.manage',
            },
            {
                title: 'Подписчики',
                href: subscribersIndex(),
                icon: Mail,
                permission: 'subscribers.manage',
            },
        ],
    },
    {
        label: 'Система',
        items: [
            {
                title: 'Пользователи',
                href: usersIndex(),
                icon: Users,
                permission: 'users.manage',
            },
            {
                title: 'Языки',
                href: languagesIndex(),
                icon: Languages,
                permission: 'settings.manage',
            },
            {
                title: 'Глобальные настройки',
                href: globalsIndex(),
                icon: Globe,
                permission: 'settings.manage',
            },
            {
                title: 'Blueprint-схемы',
                href: blueprintsIndex(),
                icon: Layers,
                permission: 'settings.manage',
            },
            {
                title: 'Редиректы',
                href: redirectsIndex(),
                icon: ArrowRightLeft,
                permission: 'settings.manage',
            },
            {
                title: 'Журнал аудита',
                href: auditLogsIndex(),
                icon: ScrollText,
                permission: 'audit.view',
            },
        ],
    },
];
