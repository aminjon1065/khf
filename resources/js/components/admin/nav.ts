import {
    Bell,
    BookOpen,
    FileText,
    FolderTree,
    Image,
    Inbox,
    Languages,
    LayoutDashboard,
    Mail,
    Mountain,
    Newspaper,
    ScrollText,
    TriangleAlert,
    Users,
} from 'lucide-react';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as alertsIndex } from '@/routes/admin/alerts';
import { index as appealsIndex } from '@/routes/admin/appeals';
import { index as auditLogsIndex } from '@/routes/admin/audit-logs';
import { index as categoriesIndex } from '@/routes/admin/categories';
import { index as documentsIndex } from '@/routes/admin/documents';
import { index as guidesIndex } from '@/routes/admin/guides';
import { index as incidentsIndex } from '@/routes/admin/incidents';
import { index as languagesIndex } from '@/routes/admin/languages';
import { index as mediaIndex } from '@/routes/admin/media';
import { index as pagesIndex } from '@/routes/admin/pages';
import { index as postsIndex } from '@/routes/admin/posts';
import { index as subscribersIndex } from '@/routes/admin/subscribers';
import { index as touristGroupsIndex } from '@/routes/admin/tourist-groups';
import { index as usersIndex } from '@/routes/admin/users';
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
        label: 'Контент',
        items: [
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
            { title: 'Медиабиблиотека', href: mediaIndex(), icon: Image },
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
                title: 'Журнал аудита',
                href: auditLogsIndex(),
                icon: ScrollText,
                permission: 'audit.view',
            },
        ],
    },
];
