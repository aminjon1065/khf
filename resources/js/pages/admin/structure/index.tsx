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
import { create, destroy, edit, index } from '@/routes/admin/structure';

type SubdivisionRow = {
    id: number;
    name: string;
    parent: string | null;
    head: string | null;
    status: string;
    status_label: string;
    staff_count: number | null;
    locales: string[];
    sort_order: number;
};

type PageProps = {
    subdivisions: Paginator<SubdivisionRow>;
    filters: DataTableFilters;
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

export default function StructureIndex({ subdivisions, filters }: PageProps) {
    const [deleting, setDeleting] = useState<SubdivisionRow | null>(null);

    const columns: DataTableColumn<SubdivisionRow>[] = [
        {
            key: 'name',
            label: 'Подразделение',
            render: (subdivision) => (
                <div>
                    <span className="font-medium">{subdivision.name}</span>
                    {subdivision.head && (
                        <span className="block text-xs text-muted-foreground">
                            {subdivision.head}
                        </span>
                    )}
                </div>
            ),
        },
        {
            key: 'parent',
            label: 'Вышестоящее',
            className: 'hidden md:table-cell',
            render: (subdivision) => subdivision.parent ?? '—',
        },
        {
            key: 'staff_count',
            label: 'Штат',
            className: 'hidden lg:table-cell',
            render: (subdivision) => subdivision.staff_count ?? '—',
        },
        {
            key: 'status',
            label: 'Статус',
            render: (subdivision) => (
                <Badge
                    variant={statusVariant[subdivision.status] ?? 'secondary'}
                >
                    {subdivision.status_label}
                </Badge>
            ),
        },
        {
            key: 'sort_order',
            label: 'Порядок',
            className: 'hidden sm:table-cell',
        },
    ];

    return (
        <>
            <Head title="Структура" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Структура</h1>
                    <p className="text-sm text-muted-foreground">
                        Структурные подразделения и их функции
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    paginator={subdivisions}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(subdivision) => subdivision.id}
                    searchPlaceholder="Поиск по названию…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Добавить подразделение
                            </Link>
                        </Button>
                    }
                    actions={(subdivision) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                asChild
                            >
                                <Link href={edit(subdivision.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                onClick={() => setDeleting(subdivision)}
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
                        <DialogTitle>Удалить подразделение?</DialogTitle>
                        <DialogDescription>
                            «{deleting?.name}» будет удалено. Дочерние
                            подразделения станут корневыми.
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

StructureIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Структура', href: index() },
    ],
};
