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
import { index, show } from '@/routes/admin/tender-bids';

type BidRow = {
    id: number;
    reference: string;
    company_name: string;
    tender: string | null;
    status: string;
    status_label: string;
    assignee: string | null;
    created_at: string | null;
};

type Option = { value: string; label: string };

type PageProps = {
    bids: Paginator<BidRow> & {
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

export default function TenderBidsIndex({
    bids,
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
            <Head title="Заявки на тендеры" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Заявки на тендеры
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Заявки организаций на участие в закупках
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
                            placeholder="Поиск по номеру или организации…"
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
                                <TableHead>Организация</TableHead>
                                <TableHead className="hidden md:table-cell">
                                    Тендер
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
                            {bids.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        Заявок нет
                                    </TableCell>
                                </TableRow>
                            ) : (
                                bids.data.map((bid) => (
                                    <TableRow key={bid.id}>
                                        <TableCell className="font-mono text-xs">
                                            {bid.reference}
                                        </TableCell>
                                        <TableCell>
                                            {bid.company_name}
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell">
                                            {bid.tender ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariant[bid.status] ??
                                                    'secondary'
                                                }
                                            >
                                                {bid.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="hidden lg:table-cell">
                                            {bid.assignee ?? '—'}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {bid.created_at}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Открыть"
                                                asChild
                                            >
                                                <Link href={show(bid.id).url}>
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

                {(bids.prev_page_url || bids.next_page_url) && (
                    <div className="flex items-center justify-end gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!bids.prev_page_url}
                            onClick={() =>
                                bids.prev_page_url &&
                                router.get(bids.prev_page_url)
                            }
                        >
                            Назад
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!bids.next_page_url}
                            onClick={() =>
                                bids.next_page_url &&
                                router.get(bids.next_page_url)
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

TenderBidsIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Заявки на тендеры', href: index() },
    ],
};
