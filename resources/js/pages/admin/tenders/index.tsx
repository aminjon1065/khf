import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { DataTable } from '@/components/admin/data-table';
import type {
    DataTableColumn,
    DataTableFilters,
    Paginator,
} from '@/components/admin/data-table';
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
import { dashboard } from '@/routes/admin';
import { create, destroy, edit, index, trash } from '@/routes/admin/tenders';

type TenderRow = {
    id: number;
    title: string;
    organizer: string | null;
    tender_number: string | null;
    type: string;
    type_label: string;
    status: string;
    status_label: string;
    lots_count: number;
    bids_count: number;
    locales: string[];
    published_at: string | null;
    deadline_at: string | null;
    is_open: boolean;
};

type PageProps = {
    tenders: Paginator<TenderRow>;
    filters: DataTableFilters;
    trashedCount: number;
};

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    published: 'default',
    draft: 'secondary',
    moderation: 'outline',
    archived: 'destructive',
};

export default function TendersIndex({
    tenders,
    filters,
    trashedCount,
}: PageProps) {
    const [deleting, setDeleting] = useState<TenderRow | null>(null);

    const columns: DataTableColumn<TenderRow>[] = [
        {
            key: 'title',
            label: 'Тендер',
            render: (tender) => (
                <div>
                    <span className="font-medium">{tender.title}</span>
                    <span className="block text-xs text-muted-foreground">
                        {tender.tender_number && (
                            <span className="font-mono">
                                {tender.tender_number}
                            </span>
                        )}
                        {tender.tender_number && tender.organizer && ' · '}
                        {tender.organizer}
                    </span>
                </div>
            ),
        },
        {
            key: 'locales',
            label: 'Языки',
            render: (tender) => (
                <div className="flex gap-1">
                    {['tj', 'ru', 'en'].map((locale) => {
                        const hasTranslation = tender.locales.includes(locale);

                        return (
                            <Badge
                                key={locale}
                                variant={hasTranslation ? 'default' : 'outline'}
                                className={
                                    hasTranslation
                                        ? 'px-1.5 text-[10px] uppercase'
                                        : 'px-1.5 text-[10px] text-muted-foreground uppercase'
                                }
                            >
                                {locale}
                            </Badge>
                        );
                    })}
                </div>
            ),
        },
        {
            key: 'type',
            label: 'Тип',
            className: 'hidden md:table-cell',
            render: (tender) => tender.type_label,
        },
        {
            key: 'bids_count',
            label: 'Заявки',
            className: 'hidden lg:table-cell',
            render: (tender) => tender.bids_count,
        },
        {
            key: 'deadline_at',
            label: 'Срок подачи',
            sortable: true,
            className: 'hidden sm:table-cell',
            render: (tender) =>
                tender.deadline_at ? (
                    <span className={tender.is_open ? '' : 'text-destructive'}>
                        {tender.deadline_at}
                    </span>
                ) : (
                    '—'
                ),
        },
        {
            key: 'status',
            label: 'Статус',
            sortable: true,
            render: (tender) => (
                <Badge variant={statusVariant[tender.status] ?? 'secondary'}>
                    {tender.status_label}
                </Badge>
            ),
        },
    ];

    return (
        <>
            <Head title="Тендеры" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Тендеры</h1>
                        <p className="text-sm text-muted-foreground">
                            Государственные закупки — торговая площадка
                        </p>
                    </div>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={trash().url}>
                            <Trash2 className="size-4" />
                            Корзина {trashedCount > 0 && `(${trashedCount})`}
                        </Link>
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    paginator={tenders}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(tender) => tender.id}
                    searchPlaceholder="Поиск по названию тендера…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Создать тендер
                            </Link>
                        </Button>
                    }
                    actions={(tender) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                asChild
                            >
                                <Link href={edit(tender.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                onClick={() => setDeleting(tender)}
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                    )}
                />
            </div>

            <Dialog
                open={Boolean(deleting)}
                onOpenChange={(open) => !open && setDeleting(null)}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Удалить тендер?</DialogTitle>
                        <DialogDescription>
                            «{deleting?.title}» будет перемещён в корзину.
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
                            В корзину
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

TendersIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Тендеры', href: index() },
    ],
};
