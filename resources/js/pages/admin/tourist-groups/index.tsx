import { Head, Link, router } from '@inertiajs/react';
import { Eye } from 'lucide-react';
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
import { index, show } from '@/routes/admin/tourist-groups';

type GroupRow = {
    id: number;
    reference: string;
    leader_name: string;
    participants_count: number;
    region: string | null;
    status: string;
    status_label: string;
    assignee: string | null;
    start_date: string | null;
};

type Option = { value: string; label: string };

type PageProps = {
    groups: Paginator<GroupRow> & {
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: { search: string; status: string | null };
    statuses: Option[];
};

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    new: 'default',
    in_progress: 'outline',
    answered: 'secondary',
    closed: 'secondary',
};

export default function TouristGroupsIndex({
    groups,
    filters,
    statuses,
}: PageProps) {
    const [search, setSearch] = useState(filters.search ?? '');

    const apply = (params: Record<string, string | undefined>) => {
        router.get(
            index().url,
            {
                search: filters.search || undefined,
                status: filters.status || undefined,
                ...params,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Тургруппы" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Заявки тургрупп</h1>
                    <p className="text-sm text-muted-foreground">
                        Регистрация туристских групп и маршрутов
                    </p>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row">
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
                            placeholder="Поиск по номеру или руководителю…"
                            className="sm:max-w-xs"
                        />
                        <Button type="submit" variant="outline">
                            Найти
                        </Button>
                    </form>
                    <Select
                        value={filters.status ?? 'all'}
                        onValueChange={(value) =>
                            apply({
                                status: value === 'all' ? undefined : value,
                            })
                        }
                    >
                        <SelectTrigger className="sm:max-w-[200px]">
                            <SelectValue placeholder="Статус" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Все статусы</SelectItem>
                            {statuses.map((status) => (
                                <SelectItem
                                    key={status.value}
                                    value={status.value}
                                >
                                    {status.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Номер</TableHead>
                                <TableHead>Руководитель</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    Участники
                                </TableHead>
                                <TableHead className="hidden md:table-cell">
                                    Регион
                                </TableHead>
                                <TableHead>Статус</TableHead>
                                <TableHead className="hidden lg:table-cell">
                                    Выход
                                </TableHead>
                                <TableHead className="w-0" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {groups.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        Заявок нет
                                    </TableCell>
                                </TableRow>
                            ) : (
                                groups.data.map((group) => (
                                    <TableRow key={group.id}>
                                        <TableCell className="font-mono text-xs">
                                            {group.reference}
                                        </TableCell>
                                        <TableCell>
                                            {group.leader_name}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {group.participants_count}
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell">
                                            {group.region ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariant[
                                                        group.status
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {group.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="hidden lg:table-cell">
                                            {group.start_date}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Открыть"
                                                asChild
                                            >
                                                <Link href={show(group.id).url}>
                                                    <Eye className="size-4" />
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                {(groups.prev_page_url || groups.next_page_url) && (
                    <div className="flex items-center justify-end gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!groups.prev_page_url}
                            onClick={() =>
                                groups.prev_page_url &&
                                router.get(groups.prev_page_url)
                            }
                        >
                            Назад
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!groups.next_page_url}
                            onClick={() =>
                                groups.next_page_url &&
                                router.get(groups.next_page_url)
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

TouristGroupsIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Тургруппы', href: index() },
    ],
};
