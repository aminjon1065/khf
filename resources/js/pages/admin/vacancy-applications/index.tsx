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
import { index, show } from '@/routes/admin/vacancy-applications';

type ApplicationRow = {
    id: number;
    reference: string;
    full_name: string;
    vacancy: string | null;
    status: string;
    status_label: string;
    assignee: string | null;
    created_at: string | null;
};

type Option = { value: string; label: string };

type PageProps = {
    applications: Paginator<ApplicationRow> & {
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

export default function VacancyApplicationsIndex({
    applications,
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
            <Head title="Заявки на вакансии" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Заявки на вакансии
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Анкеты кандидатов на государственную службу
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
                            placeholder="Поиск по номеру или ФИО…"
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
                                <TableHead>Кандидат</TableHead>
                                <TableHead className="hidden md:table-cell">
                                    Вакансия
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
                            {applications.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        Заявок нет
                                    </TableCell>
                                </TableRow>
                            ) : (
                                applications.data.map((application) => (
                                    <TableRow key={application.id}>
                                        <TableCell className="font-mono text-xs">
                                            {application.reference}
                                        </TableCell>
                                        <TableCell>
                                            {application.full_name}
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell">
                                            {application.vacancy ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariant[
                                                        application.status
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {application.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="hidden lg:table-cell">
                                            {application.assignee ?? '—'}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {application.created_at}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Открыть"
                                                asChild
                                            >
                                                <Link
                                                    href={
                                                        show(application.id).url
                                                    }
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

                {(applications.prev_page_url || applications.next_page_url) && (
                    <div className="flex items-center justify-end gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!applications.prev_page_url}
                            onClick={() =>
                                applications.prev_page_url &&
                                router.get(applications.prev_page_url)
                            }
                        >
                            Назад
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!applications.next_page_url}
                            onClick={() =>
                                applications.next_page_url &&
                                router.get(applications.next_page_url)
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

VacancyApplicationsIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Заявки на вакансии', href: index() },
    ],
};
