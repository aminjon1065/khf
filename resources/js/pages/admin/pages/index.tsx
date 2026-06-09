import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    DataTable
    
    
    
} from '@/components/admin/data-table';
import type {DataTableColumn, DataTableFilters, Paginator} from '@/components/admin/data-table';
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
import { create, destroy, edit, index, trash } from '@/routes/admin/pages';

type PageRow = {
    id: number;
    title: string;
    status: string;
    status_label: string;
    translated_locales: string[];
    updated_at: string | null;
};

type PageProps = {
    pages: Paginator<PageRow>;
    filters: DataTableFilters;
    trashedCount: number;
};

const statusVariant: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    published: 'default',
    draft: 'secondary',
    moderation: 'outline',
    archived: 'destructive',
};

export default function PagesIndex({ pages, filters, trashedCount }: PageProps) {
    const [deleting, setDeleting] = useState<PageRow | null>(null);

    const columns: DataTableColumn<PageRow>[] = [
        { key: 'title', label: 'Заголовок' },
        {
            key: 'status',
            label: 'Статус',
            render: (page) => (
                <Badge variant={statusVariant[page.status] ?? 'secondary'}>{page.status_label}</Badge>
            ),
        },
        {
            key: 'translated_locales',
            label: 'Переводы',
            render: (page) => (
                <div className="flex gap-1">
                    {page.translated_locales.map((locale) => (
                        <Badge key={locale} variant="outline" className="uppercase">
                            {locale}
                        </Badge>
                    ))}
                </div>
            ),
        },
        { key: 'updated_at', label: 'Изменена', sortable: false, className: 'hidden sm:table-cell' },
    ];

    return (
        <>
            <Head title="Страницы" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Страницы</h1>
                        <p className="text-sm text-muted-foreground">Статические страницы портала</p>
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
                    paginator={pages}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(page) => page.id}
                    searchPlaceholder="Поиск по заголовку…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Создать страницу
                            </Link>
                        </Button>
                    }
                    actions={(page) => (
                        <div className="flex justify-end gap-1">
                            <Button variant="ghost" size="icon" aria-label="Изменить" asChild>
                                <Link href={edit(page.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                onClick={() => setDeleting(page)}
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                    )}
                />
            </div>

            <Dialog open={Boolean(deleting)} onOpenChange={(open) => !open && setDeleting(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Удалить страницу?</DialogTitle>
                        <DialogDescription>
                            Страница «{deleting?.title}» будет перемещена в корзину.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleting(null)}>
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

PagesIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Страницы', href: index() },
    ],
};
