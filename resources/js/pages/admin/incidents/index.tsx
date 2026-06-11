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
import { create, destroy, edit, index, trash } from '@/routes/admin/incidents';

type IncidentRow = {
    id: number;
    title: string;
    locales: string[];
    type_label: string;
    hazard_label: string;
    hazard_color: string;
    status: string;
    status_label: string;
    region: string | null;
    occurred_at: string | null;
};

type PageProps = {
    incidents: Paginator<IncidentRow>;
    filters: DataTableFilters;
    trashedCount: number;
};

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    active: 'default',
    controlled: 'outline',
    resolved: 'secondary',
};

const localeCodes = ['tj', 'ru', 'en'];

export default function IncidentsIndex({
    incidents,
    filters,
    trashedCount,
}: PageProps) {
    const [deleting, setDeleting] = useState<IncidentRow | null>(null);

    const columns: DataTableColumn<IncidentRow>[] = [
        { key: 'title', label: 'Событие' },
        {
            key: 'locales',
            label: 'Языки',
            render: (incident) => (
                <div className="flex gap-1">
                    {localeCodes.map((code) => (
                        <Badge
                            key={code}
                            variant={
                                incident.locales.includes(code)
                                    ? 'default'
                                    : 'outline'
                            }
                            className="px-1.5 text-[10px] uppercase"
                        >
                            {code}
                        </Badge>
                    ))}
                </div>
            ),
        },
        { key: 'type_label', label: 'Тип', className: 'hidden md:table-cell' },
        {
            key: 'hazard_level',
            label: 'Уровень',
            render: (incident) => (
                <span
                    className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white"
                    style={{ backgroundColor: incident.hazard_color }}
                >
                    {incident.hazard_label}
                </span>
            ),
        },
        {
            key: 'status',
            label: 'Статус',
            sortable: true,
            render: (incident) => (
                <Badge variant={statusVariant[incident.status] ?? 'secondary'}>
                    {incident.status_label}
                </Badge>
            ),
        },
        {
            key: 'region',
            label: 'Регион',
            className: 'hidden lg:table-cell',
            render: (incident) => incident.region ?? '—',
        },
        {
            key: 'occurred_at',
            label: 'Дата',
            sortable: true,
            className: 'hidden sm:table-cell',
        },
    ];

    return (
        <>
            <Head title="Инциденты" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">События ЧС</h1>
                        <p className="text-sm text-muted-foreground">
                            Чрезвычайные ситуации и происшествия
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
                    paginator={incidents}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(incident) => incident.id}
                    searchPlaceholder="Поиск по названию…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Создать событие
                            </Link>
                        </Button>
                    }
                    actions={(incident) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                asChild
                            >
                                <Link href={edit(incident.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                onClick={() => setDeleting(incident)}
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
                        <DialogTitle>Удалить событие?</DialogTitle>
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

IncidentsIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'События ЧС', href: index() },
    ],
};
