import { Link } from '@inertiajs/react';
import {
    Bell,
    BookOpen,
    ExternalLink,
    FileText,
    FolderTree,
    Image,
    Inbox,
    Languages,
    LayoutDashboard,
    Mail,
    Mountain,
    Newspaper,
    TriangleAlert,
    Users,
} from 'lucide-react';
import { AppEmblem } from '@/components/app-emblem';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { usePermissions } from '@/hooks/use-permissions';
import { home } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as alertsIndex } from '@/routes/admin/alerts';
import { index as appealsIndex } from '@/routes/admin/appeals';
import { index as categoriesIndex } from '@/routes/admin/categories';
import { index as documentsIndex } from '@/routes/admin/documents';
import { index as mediaIndex } from '@/routes/admin/media';
import { index as guidesIndex } from '@/routes/admin/guides';
import { index as incidentsIndex } from '@/routes/admin/incidents';
import { index as languagesIndex } from '@/routes/admin/languages';
import { index as pagesIndex } from '@/routes/admin/pages';
import { index as postsIndex } from '@/routes/admin/posts';
import { index as subscribersIndex } from '@/routes/admin/subscribers';
import { index as touristGroupsIndex } from '@/routes/admin/tourist-groups';
import { index as usersIndex } from '@/routes/admin/users';
import type { NavItem } from '@/types';

type AdminNavItem = NavItem & { permission?: string };

type AdminNavGroup = {
    label: string;
    items: AdminNavItem[];
};

// CMS navigation. Items can be gated by permission; further modules (content, incidents, alerts,
// appeals, users, settings) are added here as they are built.
const navGroups: AdminNavGroup[] = [
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
            {
                title: 'Медиабиблиотека',
                href: mediaIndex(),
                icon: Image,
                // Media library is accessible if you can manage posts or documents
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
        ],
    },
];

export function AdminSidebar() {
    const { isCurrentUrl } = useCurrentUrl();
    const { can } = usePermissions();

    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={adminDashboard()} prefetch>
                                <AppEmblem className="size-8 shrink-0" />
                                <span className="flex flex-col text-left leading-tight">
                                    <span className="font-semibold">
                                        КЧС · CMS
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        Панель управления
                                    </span>
                                </span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {navGroups.map((group) => {
                    const items = group.items.filter(
                        (item) => !item.permission || can(item.permission),
                    );

                    if (items.length === 0) {
                        return null;
                    }

                    return (
                        <SidebarGroup key={group.label} className="px-2 py-0">
                            <SidebarGroupLabel>{group.label}</SidebarGroupLabel>
                            <SidebarMenu>
                                {items.map((item) => (
                                    <SidebarMenuItem key={item.title}>
                                        <SidebarMenuButton
                                            asChild
                                            isActive={isCurrentUrl(item.href)}
                                            tooltip={{ children: item.title }}
                                        >
                                            <Link href={item.href} prefetch>
                                                {item.icon && <item.icon />}
                                                <span>{item.title}</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                ))}
                            </SidebarMenu>
                        </SidebarGroup>
                    );
                })}
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            asChild
                            tooltip={{ children: 'На сайт' }}
                        >
                            <Link href={home()}>
                                <ExternalLink />
                                <span>На сайт</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
