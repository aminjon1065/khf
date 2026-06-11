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
import { create, destroy, edit, index, trash } from '@/routes/admin/alerts';

type AlertRow = {
    id: number;
    title: string;
    locales: string[];
    hazard_label: string;
    hazard_color: string;
    status: string;
    status_label: string;
    region: string | null;
    starts_at: string | null;
    ends_at: string | null;
};

type PageProps = {
    alerts: Paginator<AlertRow>;
    filters: DataTableFilters;
    trashedCount: number;
};

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    published: 'default',
    draft: 'secondary',
    cancelled: 'outline',
};

const contentLocales = ['tj', 'ru', 'en'];

export default function AlertsIndex({
    alerts,
    filters,
    trashedCount,
}: PageProps) {
    const [deleting, setDeleting] = useState<AlertRow | null>(null);

    const columns: DataTableColumn<AlertRow>[] = [
        { key: 'title', label: 'Оповещение' },
        {
            key: 'locales',
            label: 'Языки',
            render: (alert) => (
                <div className="flex gap-1">
                    {contentLocales.map((locale) => (
                        <Badge
                            key={locale}
                            variant={
                                alert.locales.includes(locale)
                                    ? 'default'
                                    : 'outline'
                            }
                            className={
                                alert.locales.includes(locale)
                                    ? 'uppercase'
                                    : 'text-muted-foreground uppercase'
                            }
                        >
                            {locale}
                        </Badge>
                    ))}
                </div>
            ),
        },
        {
            key: 'hazard_level',
            label: 'Уровень',
            render: (alert) => (
                <span
                    className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white"
                    style={{ backgroundColor: alert.hazard_color }}
                >
                    {alert.hazard_label}
                </span>
            ),
        },
        {
            key: 'status',
            label: 'Статус',
            sortable: true,
            render: (alert) => (
                <Badge variant={statusVariant[alert.status] ?? 'secondary'}>
                    {alert.status_label}
                </Badge>
            ),
        },
        {
            key: 'region',
            label: 'Регион',
            className: 'hidden lg:table-cell',
            render: (alert) => alert.region ?? 'Вся страна',
        },
        {
            key: 'starts_at',
            label: 'Начало',
            sortable: true,
            className: 'hidden sm:table-cell',
        },
    ];

    return (
        <>
            <Head title="Оповещения" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Оповещения</h1>
                        <p className="text-sm text-muted-foreground">
                            Баннеры тревоги на сайте
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
                    paginator={alerts}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(alert) => alert.id}
                    searchPlaceholder="Поиск по заголовку…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Создать оповещение
                            </Link>
                        </Button>
                    }
                    actions={(alert) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                asChild
                            >
                                <Link href={edit(alert.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                onClick={() => setDeleting(alert)}
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
                        <DialogTitle>Удалить оповещение?</DialogTitle>
                        <DialogDescription>
                            «{deleting?.title}» будет перемещено в корзину.
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

AlertsIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Оповещения', href: index() },
    ],
};
