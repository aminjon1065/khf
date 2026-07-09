import { Head, Link, usePage } from '@inertiajs/react';
import type { ShieldAlert } from 'lucide-react';
import { motion } from 'framer-motion';
import {
    ArrowRight,
    Bell,
    CalendarClock,
    CheckCircle2,
    ClipboardCheck,
    FileText,
    Inbox,
    Languages,
    Mountain,
    Plus,
    ShieldCheck,
    Siren,
    Users,
} from 'lucide-react';
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
import { index as moderationIndex } from '@/routes/admin/moderation';
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

type EditorialUpdate = {
    id: number;
    type: string;
    type_label: string;
    title: string;
    status: string;
    status_label: string;
    updated_at: string | null;
    edit_url: string;
};

type ScheduledPost = {
    id: number;
    title: string;
    published_at: string | null;
    edit_url: string;
};

type EditorialOverview = {
    moderation_queue: number | null;
    recent_updates: EditorialUpdate[];
    scheduled_posts: ScheduledPost[];
};

type DashboardProps = {
    stats: Stats;
    recentAppeals: RecentAppeal[];
    recentIncidents: RecentIncident[];
    editorial: EditorialOverview | null;
};

const statusToneClass: Record<string, string> = {
    new: 'border-red-500/20 bg-red-500/10 text-red-700 dark:text-red-400',
    active: 'border-red-500/20 bg-red-500/10 text-red-700 dark:text-red-400',
    draft: 'border-muted/50 bg-muted/30 text-muted-foreground',
    moderation:
        'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-400',
    published:
        'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
    archived: 'border-muted/50 bg-muted/30 text-muted-foreground',
    in_progress:
        'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-400',
    controlled:
        'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-400',
    answered:
        'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
    resolved:
        'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
    closed: 'border-muted/50 bg-muted/30 text-muted-foreground',
};

const statusDotClass: Record<string, string> = {
    new: 'bg-red-500',
    active: 'bg-red-500',
    draft: 'bg-muted-foreground',
    moderation: 'bg-amber-500',
    published: 'bg-emerald-500',
    archived: 'bg-muted-foreground',
    in_progress: 'bg-amber-500',
    controlled: 'bg-amber-500',
    answered: 'bg-emerald-500',
    resolved: 'bg-emerald-500',
    closed: 'bg-muted-foreground',
};

function StatusBadge({ status, label }: { status: string; label: string }) {
    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-medium tracking-wide ${statusToneClass[status] ?? 'border-border bg-muted/50 text-muted-foreground'}`}
        >
            <span
                className={`size-1.5 rounded-full ${statusDotClass[status] ?? 'bg-muted-foreground/50'}`}
            ></span>
            {label}
        </span>
    );
}

const container = {
    hidden: { opacity: 0 },
    show: {
        opacity: 1,
        transition: { staggerChildren: 0.1 },
    },
};

const item = {
    hidden: { opacity: 0, y: 10 },
    show: {
        opacity: 1,
        y: 0,
        transition: { type: 'spring', stiffness: 300, damping: 24 },
    },
};

export default function AdminDashboard({
    stats,
    recentAppeals,
    recentIncidents,
    editorial,
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
        editorial?.moderation_queue && editorial.moderation_queue > 0
            ? {
                  key: 'moderation',
                  label: 'Материалы на модерации',
                  count: editorial.moderation_queue,
                  href: moderationIndex().url,
                  icon: ClipboardCheck,
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

            <motion.div
                className="flex h-full flex-1 flex-col gap-8 p-6 lg:p-8"
                variants={container}
                initial="hidden"
                animate="show"
            >
                <motion.div
                    variants={item}
                    className="flex flex-wrap items-end justify-between gap-4"
                >
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-foreground">
                            Панель управления
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Оперативный обзор системы КЧС
                        </p>
                    </div>
                    {quickActions.length > 0 && (
                        <div className="flex flex-wrap gap-3">
                            {quickActions.map((action) => (
                                <Button
                                    key={action.label}
                                    size="sm"
                                    asChild
                                    className="rounded-full shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <Link href={action.href}>
                                        <Plus className="mr-1 size-4" />
                                        {action.label}
                                    </Link>
                                </Button>
                            ))}
                        </div>
                    )}
                </motion.div>

                {/* Needs attention */}
                {attention.length > 0 ? (
                    <motion.div
                        variants={item}
                        className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        {attention.map((item) => (
                            <Link
                                key={item.key}
                                href={item.href}
                                className="group flex items-center gap-4 rounded-2xl border border-red-500/20 bg-red-500/5 p-5 transition-all hover:bg-red-500/10 hover:shadow-sm dark:border-red-900/40 dark:bg-red-950/20"
                            >
                                <span className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-red-500 text-white shadow-sm transition-transform group-hover:scale-105">
                                    <item.icon className="size-5" />
                                </span>
                                <span className="min-w-0">
                                    <span className="block text-2xl font-bold tracking-tight text-red-600 dark:text-red-400">
                                        {item.count}
                                    </span>
                                    <span className="mt-1 block truncate text-xs font-medium text-red-600/80 dark:text-red-400/80">
                                        {item.label}
                                    </span>
                                </span>
                            </Link>
                        ))}
                    </motion.div>
                ) : (
                    <motion.div
                        variants={item}
                        className="flex items-center gap-3 rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-5 text-sm font-medium text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-400"
                    >
                        <CheckCircle2 className="size-5 shrink-0" />
                        Срочных задач нет — всё под контролем.
                    </motion.div>
                )}

                {/* KPI cards */}
                <motion.div
                    variants={item}
                    className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
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
                </motion.div>

                {editorial && (
                    <motion.div
                        variants={item}
                        className="grid gap-6 lg:grid-cols-2"
                    >
                        <Card className="flex flex-col rounded-2xl border-border/50 shadow-sm transition-all hover:shadow-md">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 border-b border-border/50 pb-4">
                                <CardTitle className="text-base font-semibold">
                                    Редакционная активность
                                </CardTitle>
                                <Link
                                    href={postsIndex().url}
                                    className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                                >
                                    Контент
                                    <ArrowRight className="size-3.5" />
                                </Link>
                            </CardHeader>
                            <CardContent className="flex-1 p-0">
                                {editorial.recent_updates.length === 0 ? (
                                    <EmptyRow text="Недавних правок нет." />
                                ) : (
                                    <ul className="divide-y divide-border/50">
                                        {editorial.recent_updates.map((item) => (
                                            <li key={`${item.type}-${item.id}`}>
                                                <Link
                                                    href={item.edit_url}
                                                    className="group flex items-center justify-between gap-4 p-4 transition-colors hover:bg-muted/30"
                                                >
                                                    <div className="min-w-0">
                                                        <p className="truncate text-sm font-medium text-foreground">
                                                            {item.title}
                                                        </p>
                                                        <p className="mt-1 text-xs text-muted-foreground">
                                                            {item.type_label}
                                                            {item.updated_at
                                                                ? ` · ${item.updated_at}`
                                                                : ''}
                                                        </p>
                                                    </div>
                                                    <StatusBadge
                                                        status={item.status}
                                                        label={item.status_label}
                                                    />
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>

                        <Card className="flex flex-col rounded-2xl border-border/50 shadow-sm transition-all hover:shadow-md">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 border-b border-border/50 pb-4">
                                <CardTitle className="flex items-center gap-2 text-base font-semibold">
                                    <CalendarClock className="size-4 text-muted-foreground" />
                                    Запланированные публикации
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex-1 p-0">
                                {editorial.scheduled_posts.length === 0 ? (
                                    <EmptyRow text="Нет материалов с отложенной публикацией." />
                                ) : (
                                    <ul className="divide-y divide-border/50">
                                        {editorial.scheduled_posts.map((post) => (
                                            <li key={post.id}>
                                                <Link
                                                    href={post.edit_url}
                                                    className="group flex items-center justify-between gap-4 p-4 transition-colors hover:bg-muted/30"
                                                >
                                                    <p className="min-w-0 truncate text-sm font-medium text-foreground">
                                                        {post.title}
                                                    </p>
                                                    <span className="shrink-0 text-xs text-muted-foreground">
                                                        {post.published_at}
                                                    </span>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    </motion.div>
                )}

                {/* Recent activity */}
                <motion.div
                    variants={item}
                    className="grid gap-6 lg:grid-cols-2"
                >
                    {stats.appeals && (
                        <Card className="flex flex-col rounded-2xl border-border/50 shadow-sm transition-all hover:shadow-md">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 border-b border-border/50 pb-4">
                                <CardTitle className="text-base font-semibold">
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
                            <CardContent className="flex-1 p-0">
                                {recentAppeals.length === 0 ? (
                                    <EmptyRow text="Обращений пока нет." />
                                ) : (
                                    <ul className="divide-y divide-border/50">
                                        {recentAppeals.map((appeal) => (
                                            <li
                                                key={appeal.id}
                                                className="group flex items-center justify-between gap-4 p-4 transition-colors hover:bg-muted/30"
                                            >
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium text-foreground">
                                                        {appeal.subject}
                                                    </p>
                                                    <p className="mt-1 text-xs text-muted-foreground transition-colors group-hover:text-muted-foreground/80">
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
                        <Card className="flex flex-col rounded-2xl border-border/50 shadow-sm transition-all hover:shadow-md">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 border-b border-border/50 pb-4">
                                <CardTitle className="text-base font-semibold">
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
                            <CardContent className="flex-1 p-0">
                                {recentIncidents.length === 0 ? (
                                    <EmptyRow text="Событий пока нет." />
                                ) : (
                                    <ul className="divide-y divide-border/50">
                                        {recentIncidents.map((incident) => (
                                            <li
                                                key={incident.id}
                                                className="group flex items-center justify-between gap-4 p-4 transition-colors hover:bg-muted/30"
                                            >
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium text-foreground">
                                                        {incident.title ??
                                                            'Без названия'}
                                                    </p>
                                                    <p className="mt-1 text-xs text-muted-foreground transition-colors group-hover:text-muted-foreground/80">
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
                </motion.div>

                {/* System (super-admin only) */}
                {stats.system && (
                    <motion.div
                        variants={item}
                        className="grid gap-6 sm:grid-cols-3"
                    >
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
                    </motion.div>
                )}
            </motion.div>
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
        <div
            className={`group flex h-full flex-col gap-2 rounded-2xl border border-border/50 bg-card p-6 shadow-sm transition-all hover:border-border hover:shadow-md ${href ? 'cursor-pointer' : ''}`}
        >
            <div className="flex items-center gap-4">
                <div className="flex size-12 items-center justify-center rounded-xl bg-primary/10 text-primary transition-transform group-hover:scale-105 dark:bg-primary/20">
                    <Icon className="size-5" />
                </div>
                <div>
                    <p className="text-sm font-medium text-muted-foreground">
                        {label}
                    </p>
                    <div className="text-3xl font-bold tracking-tight text-foreground">
                        {value}
                    </div>
                </div>
            </div>
            {hint && (
                <p className="mt-3 border-t border-border/50 pt-3 text-xs text-muted-foreground">
                    {hint}
                </p>
            )}
        </div>
    );

    return href ? (
        <Link href={href} className="block h-full">
            {body}
        </Link>
    ) : (
        body
    );
}

function EmptyRow({ text }: { text: string }) {
    return (
        <div className="flex flex-col items-center justify-center py-8 text-center text-muted-foreground">
            <p className="text-sm">{text}</p>
        </div>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: 'Панель управления', href: adminDashboard() }],
};
