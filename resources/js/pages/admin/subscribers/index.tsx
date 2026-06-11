import { Head, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { Paginator } from '@/components/admin/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { destroy, index } from '@/routes/admin/subscribers';

type SubscriberRow = {
    id: number;
    email: string;
    status: string;
    status_label: string;
    topics: string[];
    created_at: string | null;
};

type Option = { value: string; label: string };

type PageProps = {
    subscribers: Paginator<SubscriberRow> & {
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: { search: string; status: string | null };
    statuses: Option[];
    stats: { total: number; confirmed: number; pending: number };
};

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    confirmed: 'default',
    pending: 'outline',
    unsubscribed: 'secondary',
};

export default function SubscribersIndex({
    subscribers,
    filters,
    statuses,
    stats,
}: PageProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [deleting, setDeleting] = useState<SubscriberRow | null>(null);

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
            <Head title="Подписчики" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Подписчики</h1>
                    <p className="text-sm text-muted-foreground">
                        Всего: {stats.total} · Подтверждённых: {stats.confirmed}{' '}
                        · Ожидают: {stats.pending}
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
                            placeholder="Поиск по e-mail…"
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
                        <SelectTrigger className="sm:max-w-[220px]">
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
                                <TableHead>E-mail</TableHead>
                                <TableHead>Статус</TableHead>
                                <TableHead className="hidden md:table-cell">
                                    Темы
                                </TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    Дата
                                </TableHead>
                                <TableHead className="w-0" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {subscribers.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        Подписчиков нет
                                    </TableCell>
                                </TableRow>
                            ) : (
                                subscribers.data.map((subscriber) => (
                                    <TableRow key={subscriber.id}>
                                        <TableCell>
                                            {subscriber.email}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariant[
                                                        subscriber.status
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {subscriber.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell">
                                            {subscriber.topics.join(', ')}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {subscriber.created_at}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Удалить"
                                                onClick={() =>
                                                    setDeleting(subscriber)
                                                }
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                {(subscribers.prev_page_url || subscribers.next_page_url) && (
                    <div className="flex items-center justify-end gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!subscribers.prev_page_url}
                            onClick={() =>
                                subscribers.prev_page_url &&
                                router.get(subscribers.prev_page_url)
                            }
                        >
                            Назад
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={!subscribers.next_page_url}
                            onClick={() =>
                                subscribers.next_page_url &&
                                router.get(subscribers.next_page_url)
                            }
                        >
                            Вперёд
                        </Button>
                    </div>
                )}
            </div>

            <Dialog
                open={Boolean(deleting)}
                onOpenChange={(open) => !open && setDeleting(null)}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Удалить подписчика?</DialogTitle>
                        <DialogDescription>
                            «{deleting?.email}» будет удалён из базы подписок.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeleting(null)}
                        >
                            Отмена
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => {
                                if (deleting) {
                                    router.delete(destroy(deleting.id).url, {
                                        preserveScroll: true,
                                        onSuccess: () => setDeleting(null),
                                    });
                                }
                            }}
                        >
                            Удалить
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

SubscribersIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Подписчики', href: index() },
    ],
};
