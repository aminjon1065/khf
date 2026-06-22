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
import { index, show } from '@/routes/admin/appeals';

type AppealRow = {
    id: number;
    reference: string;
    subject: string;
    category_label: string;
    status: string;
    status_label: string;
    assignee: string | null;
    created_at: string | null;
};

type Option = { value: string; label: string };

type PageProps = {
    appeals: Paginator<AppealRow> & {
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

export default function AppealsIndex({
    appeals,
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
            <Head title="Обращения" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Обращения граждан
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Электронная приёмная — очередь обработки
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
                            placeholder="Поиск по номеру, теме, имени…"
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

                    <Button variant="secondary" asChild>
                        <a
                            href={`/admin/appeals/export?search=${filters.search ?? ''}&status=${filters.status ?? ''}`}
                        >
                            Экспорт CSV
                        </a>
                    </Button>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Номер</TableHead>
                                <TableHead>Тема</TableHead>
                                <TableHead className="hidden md:table-cell">
                                    Категория
                                </TableHead>
                                <TableHead>Статус</TableHead>
                                <TableHead className="hidden lg:table-cell">
                                    Ответственный
                                </TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    Подано
                                </TableHead>
                                <TableHead className="w-0" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {appeals.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        Обращений нет
                                    </TableCell>
                                </TableRow>
                            ) : (
                                appeals.data.map((appeal) => (
                                    <TableRow key={appeal.id}>
                                        <TableCell className="font-mono text-xs">
                                            {appeal.reference}
                                        </TableCell>
                                        <TableCell>{appeal.subject}</TableCell>
                                        <TableCell className="hidden md:table-cell">
                                            {appeal.category_label}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariant[
                                                        appeal.status
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {appeal.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="hidden lg:table-cell">
                                            {appeal.assignee ?? '—'}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {appeal.created_at}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Открыть"
                                                asChild
                                            >
                                                <Link
                                                    href={show(appeal.id).url}
                                                >
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

                {(appeals.prev_page_url || appeals.next_page_url) && (
                    <div className="flex items-center justify-end gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!appeals.prev_page_url}
                            onClick={() =>
                                appeals.prev_page_url &&
                                router.get(appeals.prev_page_url)
                            }
                        >
                            Назад
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!appeals.next_page_url}
                            onClick={() =>
                                appeals.next_page_url &&
                                router.get(appeals.next_page_url)
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

AppealsIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Обращения', href: index() },
    ],
};
