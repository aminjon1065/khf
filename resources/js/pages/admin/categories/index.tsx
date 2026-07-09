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
import { create, destroy, edit, index } from '@/routes/admin/categories';

const supportedLocales = ['tj', 'ru', 'en'];

type CategoryRow = {
    id: number;
    name: string;
    locales: string[];
    sort_order: number;
};

type PageProps = {
    categories: Paginator<CategoryRow>;
    filters: DataTableFilters;
};

export default function CategoriesIndex({ categories, filters }: PageProps) {
    const [deleting, setDeleting] = useState<CategoryRow | null>(null);

    const columns: DataTableColumn<CategoryRow>[] = [
        { key: 'name', label: 'Название' },
        {
            key: 'locales',
            label: 'Языки',
            render: (category) => (
                <div className="flex gap-1">
                    {supportedLocales.map((locale) => {
                        const hasTranslation =
                            category.locales.includes(locale);

                        return (
                            <Badge
                                key={locale}
                                variant={hasTranslation ? 'default' : 'outline'}
                                className={
                                    hasTranslation
                                        ? 'uppercase'
                                        : 'text-muted-foreground uppercase'
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
            key: 'sort_order',
            label: 'Порядок',
            sortable: true,
            className: 'hidden sm:table-cell',
        },
    ];

    return (
        <>
            <Head title="Рубрики" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Рубрики</h1>
                    <p className="text-sm text-muted-foreground">
                        Категории для группировки новостей и материалов в разделе «Новости»
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    paginator={categories}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(category) => category.id}
                    searchPlaceholder="Поиск по названию…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Создать рубрику
                            </Link>
                        </Button>
                    }
                    actions={(category) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                asChild
                            >
                                <Link href={edit(category.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                onClick={() => setDeleting(category)}
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
                        <DialogTitle>Удалить рубрику?</DialogTitle>
                        <DialogDescription>
                            Рубрика «{deleting?.name}» будет удалена. Это
                            действие нельзя отменить.
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

CategoriesIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Рубрики', href: index() },
    ],
};
