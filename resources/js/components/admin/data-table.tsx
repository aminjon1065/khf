import { router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ArrowUpDown, ChevronLeft, ChevronRight, Search } from 'lucide-react';
import { useEffect, useRef, useState  } from 'react';
import type {ReactNode} from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export type DataTableColumn<T> = {
    key: string;
    label: string;
    sortable?: boolean;
    className?: string;
    render?: (row: T) => ReactNode;
};

export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type DataTableFilters = {
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
};

type DataTableProps<T> = {
    columns: DataTableColumn<T>[];
    paginator: Paginator<T>;
    filters: DataTableFilters;
    baseUrl: string;
    getRowId: (row: T) => string | number;
    searchPlaceholder?: string;
    actions?: (row: T) => ReactNode;
    toolbar?: ReactNode;
    emptyMessage?: string;
};

/**
 * Reusable server-side data table for the CMS: debounced search, sortable columns and pagination,
 * all driven through Inertia visits so state stays on the server (ТЗ §7.1).
 */
export function DataTable<T>({
    columns,
    paginator,
    filters,
    baseUrl,
    getRowId,
    searchPlaceholder = 'Поиск…',
    actions,
    toolbar,
    emptyMessage = 'Ничего не найдено',
}: DataTableProps<T>) {
    const [search, setSearch] = useState(filters.search ?? '');
    const isFirstRender = useRef(true);

    const visit = (params: Record<string, unknown>) => {
        router.get(
            baseUrl,
            { search: filters.search, sort: filters.sort, direction: filters.direction, ...params },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const handler = setTimeout(() => {
            if (search !== (filters.search ?? '')) {
                visit({ search: search || undefined, page: 1 });
            }
        }, 300);

        return () => clearTimeout(handler);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const toggleSort = (key: string) => {
        const direction = filters.sort === key && filters.direction === 'asc' ? 'desc' : 'asc';
        visit({ sort: key, direction, page: 1 });
    };

    const sortIcon = (key: string) => {
        if (filters.sort !== key) {
            return <ArrowUpDown className="size-3.5 opacity-50" />;
        }

        return filters.direction === 'desc' ? <ArrowDown className="size-3.5" /> : <ArrowUp className="size-3.5" />;
    };

    const goTo = (url: string | null) => {
        if (url) {
            router.get(url, {}, { preserveState: true, preserveScroll: true, replace: true });
        }
    };

    const colSpan = columns.length + (actions ? 1 : 0);

    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="relative w-full sm:max-w-xs">
                    <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder={searchPlaceholder}
                        className="pl-8"
                    />
                </div>
                {toolbar}
            </div>

            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            {columns.map((column) => (
                                <TableHead key={column.key} className={column.className}>
                                    {column.sortable ? (
                                        <button
                                            type="button"
                                            onClick={() => toggleSort(column.key)}
                                            className="inline-flex items-center gap-1.5 hover:text-foreground"
                                        >
                                            {column.label}
                                            {sortIcon(column.key)}
                                        </button>
                                    ) : (
                                        column.label
                                    )}
                                </TableHead>
                            ))}
                            {actions && <TableHead className="w-0 text-right">Действия</TableHead>}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {paginator.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={colSpan} className="h-24 text-center text-muted-foreground">
                                    {emptyMessage}
                                </TableCell>
                            </TableRow>
                        ) : (
                            paginator.data.map((row) => (
                                <TableRow key={getRowId(row)}>
                                    {columns.map((column) => (
                                        <TableCell key={column.key} className={column.className}>
                                            {column.render
                                                ? column.render(row)
                                                : String((row as Record<string, unknown>)[column.key] ?? '')}
                                        </TableCell>
                                    ))}
                                    {actions && <TableCell className="text-right">{actions(row)}</TableCell>}
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            <div className="flex items-center justify-between text-sm text-muted-foreground">
                <span>
                    {paginator.total > 0 ? `${paginator.from}–${paginator.to} из ${paginator.total}` : '0 из 0'}
                </span>
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!paginator.prev_page_url}
                        onClick={() => goTo(paginator.prev_page_url)}
                    >
                        <ChevronLeft className="size-4" />
                        Назад
                    </Button>
                    <span>
                        Стр. {paginator.current_page} из {paginator.last_page}
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!paginator.next_page_url}
                        onClick={() => goTo(paginator.next_page_url)}
                    >
                        Вперёд
                        <ChevronRight className="size-4" />
                    </Button>
                </div>
            </div>
        </div>
    );
}
