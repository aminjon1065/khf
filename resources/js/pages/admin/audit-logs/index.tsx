import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import type { Paginator } from '@/components/admin/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes/admin';
import { index } from '@/routes/admin/audit-logs';

type LogRow = {
    id: number;
    event: string;
    event_label: string;
    log_name: string | null;
    subject_label: string | null;
    subject_id: number | null;
    description: string;
    causer: string | null;
    causer_email: string | null;
    ip: string | null;
    changes: string[];
    created_at: string | null;
};

type Option = { value: string; label: string };

type PageProps = {
    logs: Paginator<LogRow> & {
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: { search: string; event: string | null; log: string | null };
    events: Option[];
};

const eventVariant: Record<
    string,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    created: 'default',
    updated: 'outline',
    deleted: 'destructive',
    restored: 'secondary',
    login: 'secondary',
    logout: 'secondary',
    login_failed: 'destructive',
    lockout: 'destructive',
    two_factor_enabled: 'secondary',
    two_factor_confirmed: 'secondary',
    two_factor_disabled: 'outline',
};

const logOptions: Option[] = [
    { value: 'default', label: 'Контент' },
    { value: 'auth', label: 'Безопасность' },
];

export default function AuditLogsIndex({ logs, filters, events }: PageProps) {
    const [search, setSearch] = useState(filters.search ?? '');

    const apply = (params: Record<string, string | undefined>) => {
        router.get(
            index().url,
            {
                search: filters.search || undefined,
                event: filters.event || undefined,
                log: filters.log || undefined,
                ...params,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Журнал аудита" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Журнал аудита
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Действия в CMS и события безопасности — только для
                        чтения
                    </p>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <form
                        className="flex flex-1 gap-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            apply({ search: search || undefined });
                        }}
                    >
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Поиск по описанию или пользователю…"
                            className="bg-card sm:max-w-xs"
                        />
                        <Button type="submit" variant="outline">
                            Найти
                        </Button>
                    </form>
                    <Select
                        value={filters.event ?? 'all'}
                        onValueChange={(value) =>
                            apply({
                                event: value === 'all' ? undefined : value,
                            })
                        }
                    >
                        <SelectTrigger className="bg-card sm:max-w-[200px]">
                            <SelectValue placeholder="Событие" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Все события</SelectItem>
                            {events.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.log ?? 'all'}
                        onValueChange={(value) =>
                            apply({ log: value === 'all' ? undefined : value })
                        }
                    >
                        <SelectTrigger className="bg-card sm:max-w-[180px]">
                            <SelectValue placeholder="Тип" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Все типы</SelectItem>
                            {logOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/40 hover:bg-muted/40">
                                <TableHead className="text-xs tracking-wide uppercase">
                                    Время
                                </TableHead>
                                <TableHead className="text-xs tracking-wide uppercase">
                                    Событие
                                </TableHead>
                                <TableHead className="text-xs tracking-wide uppercase">
                                    Детали
                                </TableHead>
                                <TableHead className="text-xs tracking-wide uppercase">
                                    Пользователь
                                </TableHead>
                                <TableHead className="hidden text-xs tracking-wide uppercase lg:table-cell">
                                    IP
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {logs.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        Записей нет
                                    </TableCell>
                                </TableRow>
                            ) : (
                                logs.data.map((log) => (
                                    <TableRow
                                        key={log.id}
                                        className="hover:bg-muted/40"
                                    >
                                        <TableCell className="text-sm whitespace-nowrap text-muted-foreground">
                                            {log.created_at}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    eventVariant[log.event] ??
                                                    'secondary'
                                                }
                                            >
                                                {log.event_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {log.subject_label ? (
                                                <div className="space-y-0.5">
                                                    <span className="text-sm">
                                                        {log.subject_label}
                                                        {log.subject_id !==
                                                            null && (
                                                            <span className="text-muted-foreground">
                                                                {' '}
                                                                #
                                                                {log.subject_id}
                                                            </span>
                                                        )}
                                                    </span>
                                                    {log.changes.length > 0 && (
                                                        <p className="text-xs text-muted-foreground">
                                                            {log.changes.join(
                                                                ', ',
                                                            )}
                                                        </p>
                                                    )}
                                                </div>
                                            ) : (
                                                <span className="text-sm">
                                                    {log.description}
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {log.causer ? (
                                                <div className="space-y-0.5">
                                                    <span className="text-sm">
                                                        {log.causer}
                                                    </span>
                                                    {log.causer_email && (
                                                        <p className="text-xs text-muted-foreground">
                                                            {log.causer_email}
                                                        </p>
                                                    )}
                                                </div>
                                            ) : (
                                                <span className="text-sm text-muted-foreground">
                                                    Система
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="hidden font-mono text-xs text-muted-foreground lg:table-cell">
                                            {log.ip ?? '—'}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                {(logs.prev_page_url || logs.next_page_url) && (
                    <div className="flex items-center justify-end gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!logs.prev_page_url}
                            onClick={() =>
                                logs.prev_page_url &&
                                router.get(logs.prev_page_url)
                            }
                        >
                            Назад
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!logs.next_page_url}
                            onClick={() =>
                                logs.next_page_url &&
                                router.get(logs.next_page_url)
                            }
                        >
                            Вперёд
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}

AuditLogsIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Журнал аудита', href: index() },
    ],
};
