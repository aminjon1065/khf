import { Link } from '@inertiajs/react';
import { Bell, ExternalLink, FileText, FolderTree, Languages, LayoutDashboard, Newspaper, TriangleAlert, Users } from 'lucide-react';
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
import { index as categoriesIndex } from '@/routes/admin/categories';
import { index as incidentsIndex } from '@/routes/admin/incidents';
import { index as languagesIndex } from '@/routes/admin/languages';
import { index as pagesIndex } from '@/routes/admin/pages';
import { index as postsIndex } from '@/routes/admin/posts';
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
        items: [{ title: 'Панель управления', href: adminDashboard(), icon: LayoutDashboard }],
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
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={adminDashboard()} prefetch>
                                <span className="flex flex-col text-left leading-tight">
                                    <span className="font-semibold">КЧС · CMS</span>
                                    <span className="text-xs text-muted-foreground">Панель управления</span>
                                </span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {navGroups.map((group) => {
                    const items = group.items.filter((item) => !item.permission || can(item.permission));

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
                        <SidebarMenuButton asChild tooltip={{ children: 'На сайт' }}>
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
