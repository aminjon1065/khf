import { Head, Link, usePage } from '@inertiajs/react';
import type { ShieldAlert } from 'lucide-react';
import {
    ArrowRight,
    Bell,
    CheckCircle2,
    FileText,
    Inbox,
    Languages,
    Mountain,
    Plus,
    ShieldCheck,
    Siren,
    Users,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    create as alertsCreate,
    index as alertsIndex,
} from '@/routes/admin/alerts';
import { index as appealsIndex } from '@/routes/admin/appeals';
import {
    create as incidentsCreate,
    index as incidentsIndex,
} from '@/routes/admin/incidents';
import {
    create as postsCreate,
    index as postsIndex,
} from '@/routes/admin/posts';
import { index as subscribersIndex } from '@/routes/admin/subscribers';
import { index as touristGroupsIndex } from '@/routes/admin/tourist-groups';
import { index as usersIndex } from '@/routes/admin/users';
import type { SharedData } from '@/types';

type Stats = {
    appeals: { new: number; in_progress: number; total: number } | null;
    incidents: { active: number; controlled: number; total: number } | null;
    alerts: { active: number; total: number } | null;
    touristGroups: { pending: number; on_route: number; total: number } | null;
    subscribers: { confirmed: number; pending: number } | null;
    content: {
        posts_published: number;
        posts_total: number;
        pages: number;
        documents: number;
        guides: number;
    } | null;
    system: { users: number; languages: number; roles: number } | null;
};

type RecentAppeal = {
    id: number;
    reference: string;
    subject: string;
    status: string;
    status_label: string;
    created_at: string | null;
};

type RecentIncident = {
    id: number;
    title: string | null;
    status: string;
    status_label: string;
    hazard_level: string;
    occurred_at: string | null;
};

type DashboardProps = {
    stats: Stats;
    recentAppeals: RecentAppeal[];
    recentIncidents: RecentIncident[];
};

const statusToneClass: Record<string, string> = {
    new: 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-300',
    active: 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-300',
    in_progress:
        'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
    controlled:
        'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
    answered:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
    resolved:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
    closed: 'bg-muted text-muted-foreground',
};

function StatusBadge({ status, label }: { status: string; label: string }) {
    return (
        <Badge variant="secondary" className={statusToneClass[status] ?? ''}>
            {label}
        </Badge>
    );
}

export default function AdminDashboard({
    stats,
    recentAppeals,
    recentIncidents,
}: DashboardProps) {
    const { auth } = usePage<SharedData>().props;
    const permissions = auth?.permissions ?? [];
    const can = (permission: string) => permissions.includes(permission);

    // Things that need a human now — surfaced first.
    const attention = [
        stats.alerts && stats.alerts.active > 0
            ? {
                  key: 'alerts',
                  label: 'Активные оповещения',
                  count: stats.alerts.active,
                  href: alertsIndex().url,
                  icon: Bell,
              }
            : null,
        stats.incidents && stats.incidents.active > 0
            ? {
                  key: 'incidents',
                  label: 'Активные события ЧС',
                  count: stats.incidents.active,
                  href: incidentsIndex().url,
                  icon: Siren,
              }
            : null,
        stats.appeals && stats.appeals.new > 0
            ? {
                  key: 'appeals',
                  label: 'Новые обращения',
                  count: stats.appeals.new,
                  href: appealsIndex().url,
                  icon: Inbox,
              }
            : null,
        stats.touristGroups && stats.touristGroups.pending > 0
            ? {
                  key: 'tourist',
                  label: 'Заявки тургрупп на рассмотрении',
                  count: stats.touristGroups.pending,
                  href: touristGroupsIndex().url,
                  icon: Mountain,
              }
            : null,
    ].filter((item): item is NonNullable<typeof item> => item !== null);

    const quickActions = [
        can('posts.manage')
            ? { label: 'Новость', href: postsCreate().url }
            : null,
        can('incidents.manage')
            ? { label: 'Событие ЧС', href: incidentsCreate().url }
            : null,
        can('alerts.manage')
            ? { label: 'Оповещение', href: alertsCreate().url }
            : null,
    ].filter((item): item is NonNullable<typeof item> => item !== null);

    return (
        <>
            <Head title="Панель управления" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Панель управления
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Оперативный обзор системы КЧС
                        </p>
                    </div>
                    {quickActions.length > 0 && (
                        <div className="flex flex-wrap gap-2">
                            {quickActions.map((action) => (
                                <Button key={action.label} size="sm" asChild>
                                    <Link href={action.href}>
                                        <Plus className="size-4" />
                                        {action.label}
                                    </Link>
                                </Button>
                            ))}
                        </div>
                    )}
                </div>

                {/* Needs attention */}
                {attention.length > 0 ? (
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {attention.map((item) => (
                            <Link
                                key={item.key}
                                href={item.href}
                                className="group flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 transition-colors hover:bg-red-100 dark:border-red-900/50 dark:bg-red-950/30 dark:hover:bg-red-950/50"
                            >
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-red-600 text-white">
                                    <item.icon className="size-5" />
                                </span>
                                <span className="min-w-0">
                                    <span className="block text-2xl leading-none font-bold text-red-700 dark:text-red-300">
                                        {item.count}
                                    </span>
                                    <span className="mt-1 block truncate text-xs font-medium text-red-700/80 dark:text-red-300/80">
                                        {item.label}
                                    </span>
                                </span>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <div className="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                        <CheckCircle2 className="size-5 shrink-0" />
                        Срочных задач нет — всё под контролем.
                    </div>
                )}

                {/* KPI cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {stats.appeals && (
                        <StatCard
                            label="Обращения"
                            value={stats.appeals.total}
                            hint={`Новых: ${stats.appeals.new} · В работе: ${stats.appeals.in_progress}`}
                            icon={Inbox}
                            href={appealsIndex().url}
                        />
                    )}
                    {stats.incidents && (
                        <StatCard
                            label="События ЧС"
                            value={stats.incidents.total}
                            hint={`Активных: ${stats.incidents.active} · Под контролем: ${stats.incidents.controlled}`}
                            icon={Siren}
                            href={incidentsIndex().url}
                        />
                    )}
                    {stats.alerts && (
                        <StatCard
                            label="Оповещения"
                            value={stats.alerts.total}
                            hint={`Активных сейчас: ${stats.alerts.active}`}
                            icon={Bell}
                            href={alertsIndex().url}
                        />
                    )}
                    {stats.touristGroups && (
                        <StatCard
                            label="Тургруппы"
                            value={stats.touristGroups.total}
                            hint={`На маршруте: ${stats.touristGroups.on_route} · Заявок: ${stats.touristGroups.pending}`}
                            icon={Mountain}
                            href={touristGroupsIndex().url}
                        />
                    )}
                    {stats.subscribers && (
                        <StatCard
                            label="Подписчики"
                            value={stats.subscribers.confirmed}
                            hint={`Ожидают подтверждения: ${stats.subscribers.pending}`}
                            icon={Users}
                            href={subscribersIndex().url}
                        />
                    )}
                    {stats.content && (
                        <StatCard
                            label="Новости"
                            value={stats.content.posts_published}
                            hint={`Всего: ${stats.content.posts_total} · Памяток: ${stats.content.guides} · Документов: ${stats.content.documents}`}
                            icon={FileText}
                            href={postsIndex().url}
                        />
                    )}
                </div>

                {/* Recent activity */}
                <div className="grid gap-4 lg:grid-cols-2">
                    {stats.appeals && (
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                                <CardTitle className="text-base">
                                    Последние обращения
                                </CardTitle>
                                <Link
                                    href={appealsIndex().url}
                                    className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                                >
                                    Все
                                    <ArrowRight className="size-3.5" />
                                </Link>
                            </CardHeader>
                            <CardContent>
                                {recentAppeals.length === 0 ? (
                                    <EmptyRow text="Обращений пока нет." />
                                ) : (
                                    <ul className="divide-y">
                                        {recentAppeals.map((appeal) => (
                                            <li
                                                key={appeal.id}
                                                className="flex items-center justify-between gap-3 py-2.5"
                                            >
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium">
                                                        {appeal.subject}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {appeal.reference}
                                                        {appeal.created_at
                                                            ? ` · ${appeal.created_at}`
                                                            : ''}
                                                    </p>
                                                </div>
                                                <StatusBadge
                                                    status={appeal.status}
                                                    label={appeal.status_label}
                                                />
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    {stats.incidents && (
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                                <CardTitle className="text-base">
                                    Последние события ЧС
                                </CardTitle>
                                <Link
                                    href={incidentsIndex().url}
                                    className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                                >
                                    Все
                                    <ArrowRight className="size-3.5" />
                                </Link>
                            </CardHeader>
                            <CardContent>
                                {recentIncidents.length === 0 ? (
                                    <EmptyRow text="Событий пока нет." />
                                ) : (
                                    <ul className="divide-y">
                                        {recentIncidents.map((incident) => (
                                            <li
                                                key={incident.id}
                                                className="flex items-center justify-between gap-3 py-2.5"
                                            >
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium">
                                                        {incident.title ??
                                                            'Без названия'}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {incident.occurred_at ??
                                                            ''}
                                                    </p>
                                                </div>
                                                <StatusBadge
                                                    status={incident.status}
                                                    label={
                                                        incident.status_label
                                                    }
                                                />
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* System (super-admin only) */}
                {stats.system && (
                    <div className="grid gap-4 sm:grid-cols-3">
                        <StatCard
                            label="Пользователи"
                            value={stats.system.users}
                            icon={Users}
                            href={usersIndex().url}
                        />
                        <StatCard
                            label="Языки"
                            value={stats.system.languages}
                            icon={Languages}
                        />
                        <StatCard
                            label="Роли"
                            value={stats.system.roles}
                            icon={ShieldCheck}
                        />
                    </div>
                )}
            </div>
        </>
    );
}

function StatCard({
    label,
    value,
    hint,
    icon: Icon,
    href,
}: {
    label: string;
    value: number;
    hint?: string;
    icon: typeof ShieldAlert;
    href?: string;
}) {
    const body = (
        <Card
            className={href ? 'transition-colors hover:border-primary/40' : ''}
        >
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {label}
                </CardTitle>
                <Icon className="size-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div className="text-3xl font-semibold">{value}</div>
                {hint && (
                    <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
                )}
            </CardContent>
        </Card>
    );

    return href ? (
        <Link href={href} className="block">
            {body}
        </Link>
    ) : (
        body
    );
}

function EmptyRow({ text }: { text: string }) {
    return (
        <p className="py-6 text-center text-sm text-muted-foreground">{text}</p>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: 'Панель управления', href: adminDashboard() }],
};
