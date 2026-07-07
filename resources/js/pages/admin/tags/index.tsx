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
import { create, destroy, edit, index } from '@/routes/admin/tags';

const supportedLocales = ['tj', 'ru', 'en'];

type TagRow = {
    id: number;
    name: string;
    locales: string[];
    posts_count: number;
    documents_count: number;
};

type PageProps = {
    tags: Paginator<TagRow>;
    filters: DataTableFilters;
};

export default function TagsIndex({ tags, filters }: PageProps) {
    const [deleting, setDeleting] = useState<TagRow | null>(null);

    const columns: DataTableColumn<TagRow>[] = [
        { key: 'name', label: 'Название' },
        {
            key: 'locales',
            label: 'Языки',
            render: (tag) => (
                <div className="flex gap-1">
                    {supportedLocales.map((locale) => {
                        const hasTranslation = tag.locales.includes(locale);

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
            key: 'posts_count',
            label: 'Материалы',
            className: 'hidden sm:table-cell',
        },
        {
            key: 'documents_count',
            label: 'Документы',
            className: 'hidden md:table-cell',
        },
    ];

    return (
        <>
            <Head title="Теги" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Теги</h1>
                    <p className="text-sm text-muted-foreground">
                        Метки для материалов и документов
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    paginator={tags}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(tag) => tag.id}
                    searchPlaceholder="Поиск по названию…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Создать тег
                            </Link>
                        </Button>
                    }
                    actions={(tag) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                asChild
                            >
                                <Link href={edit(tag.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                onClick={() => setDeleting(tag)}
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
                        <DialogTitle>Удалить тег?</DialogTitle>
                        <DialogDescription>
                            Тег «{deleting?.name}» будет удалён. Привязки к
                            материалам и документам будут сняты.
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

TagsIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Теги', href: index() },
    ],
};
