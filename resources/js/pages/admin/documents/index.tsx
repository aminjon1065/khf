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
import { create, destroy, edit, index, trash } from '@/routes/admin/documents';

type DocumentRow = {
    id: number;
    name: string;
    type: string;
    type_label: string;
    status: string;
    status_label: string;
    locales: string[];
    document_date: string | null;
    files_count: number;
};

type PageProps = {
    documents: Paginator<DocumentRow>;
    filters: DataTableFilters;
    trashedCount: number;
};

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    published: 'default',
    draft: 'secondary',
    archived: 'outline',
};

const portalLocales = ['tj', 'ru', 'en'] as const;

export default function DocumentsIndex({ documents, filters, trashedCount }: PageProps) {
    const [deleting, setDeleting] = useState<DocumentRow | null>(null);

    const columns: DataTableColumn<DocumentRow>[] = [
        { key: 'name', label: 'Наименование' },
        {
            key: 'locales',
            label: 'Языки',
            render: (document) => (
                <div className="flex gap-1">
                    {portalLocales.map((locale) => (
                        <Badge
                            key={locale}
                            variant={document.locales.includes(locale) ? 'default' : 'outline'}
                            className={`uppercase ${document.locales.includes(locale) ? '' : 'text-muted-foreground'}`}
                        >
                            {locale}
                        </Badge>
                    ))}
                </div>
            ),
        },
        { key: 'type_label', label: 'Тип', className: 'hidden md:table-cell' },
        {
            key: 'status',
            label: 'Статус',
            sortable: true,
            render: (document) => <Badge variant={statusVariant[document.status] ?? 'secondary'}>{document.status_label}</Badge>,
        },
        { key: 'files_count', label: 'Файлы', render: (document) => String(document.files_count) },
        { key: 'document_date', label: 'Дата', sortable: true, className: 'hidden sm:table-cell' },
    ];

    return (
        <>
            <Head title="Документы" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Документы</h1>
                        <p className="text-sm text-muted-foreground">Реестр нормативных и ведомственных документов</p>
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
                    paginator={documents}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(document) => document.id}
                    searchPlaceholder="Поиск по наименованию…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Добавить документ
                            </Link>
                        </Button>
                    }
                    actions={(document) => (
                        <div className="flex justify-end gap-1">
                            <Button variant="ghost" size="icon" aria-label="Изменить" asChild>
                                <Link href={edit(document.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button variant="ghost" size="icon" aria-label="Удалить" onClick={() => setDeleting(document)}>
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                    )}
                />
            </div>

            <Dialog open={Boolean(deleting)} onOpenChange={(open) => !open && setDeleting(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Удалить документ?</DialogTitle>
                        <DialogDescription>«{deleting?.name}» будет перемещён в корзину.</DialogDescription>
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

DocumentsIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Документы', href: index() },
    ],
};
