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
import { create, destroy, edit, index } from '@/routes/admin/services';

type ServiceRow = {
    id: number;
    title: string;
    category: string;
    category_label: string;
    status: string;
    status_label: string;
    is_online: boolean;
    locales: string[];
    sort_order: number;
};

type PageProps = {
    services: Paginator<ServiceRow>;
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

export default function ServicesIndex({ services, filters }: PageProps) {
    const [deleting, setDeleting] = useState<ServiceRow | null>(null);

    const columns: DataTableColumn<ServiceRow>[] = [
        { key: 'title', label: 'Название' },
        {
            key: 'category',
            label: 'Категория',
            render: (service) => (
                <Badge variant="outline">{service.category_label}</Badge>
            ),
        },
        {
            key: 'status',
            label: 'Статус',
            render: (service) => (
                <Badge variant={statusVariant[service.status] ?? 'secondary'}>
                    {service.status_label}
                </Badge>
            ),
        },
        {
            key: 'is_online',
            label: 'Онлайн',
            render: (service) =>
                service.is_online ? (
                    <Badge>Да</Badge>
                ) : (
                    <span className="text-muted-foreground">—</span>
                ),
        },
    ];

    return (
        <>
            <Head title="Услуги" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Услуги</h1>
                    <p className="text-sm text-muted-foreground">
                        Каталог государственных услуг
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    paginator={services}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(service) => service.id}
                    searchPlaceholder="Поиск по названию…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Добавить услугу
                            </Link>
                        </Button>
                    }
                    actions={(service) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                asChild
                            >
                                <Link href={edit(service.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                onClick={() => setDeleting(service)}
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
                        <DialogTitle>Удалить услугу?</DialogTitle>
                        <DialogDescription>
                            «{deleting?.title}» будет удалена. Это действие нельзя
                            отменить.
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

ServicesIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Услуги', href: index() },
    ],
};
