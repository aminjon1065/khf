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
import { create, destroy, edit, index } from '@/routes/admin/gallery';

type GalleryRow = {
    id: number;
    title: string;
    status: string;
    status_label: string;
    photos_count: number;
    cover_url: string | null;
    locales: string[];
    sort_order: number;
};

type PageProps = {
    galleries: Paginator<GalleryRow>;
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

export default function GalleryIndex({ galleries, filters }: PageProps) {
    const [deleting, setDeleting] = useState<GalleryRow | null>(null);

    const columns: DataTableColumn<GalleryRow>[] = [
        {
            key: 'title',
            label: 'Галерея',
            render: (gallery) => (
                <div className="flex items-center gap-3">
                    {gallery.cover_url ? (
                        <img
                            src={gallery.cover_url}
                            alt=""
                            className="h-10 w-16 rounded object-cover"
                        />
                    ) : (
                        <div className="h-10 w-16 rounded bg-muted" />
                    )}
                    <span className="font-medium">{gallery.title}</span>
                </div>
            ),
        },
        {
            key: 'photos_count',
            label: 'Фото',
            className: 'hidden sm:table-cell',
            render: (gallery) => gallery.photos_count,
        },
        {
            key: 'locales',
            label: 'Языки',
            render: (gallery) => (
                <div className="flex gap-1">
                    {['tj', 'ru', 'en'].map((locale) => {
                        const hasTranslation = gallery.locales.includes(locale);

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
            key: 'status',
            label: 'Статус',
            render: (gallery) => (
                <Badge variant={statusVariant[gallery.status] ?? 'secondary'}>
                    {gallery.status_label}
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
            <Head title="Фотогалерея" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Фотогалерея</h1>
                    <p className="text-sm text-muted-foreground">
                        Фото- и видеоматериалы о мероприятиях
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    paginator={galleries}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(gallery) => gallery.id}
                    searchPlaceholder="Поиск по названию…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Создать галерею
                            </Link>
                        </Button>
                    }
                    actions={(gallery) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                asChild
                            >
                                <Link href={edit(gallery.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                onClick={() => setDeleting(gallery)}
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
                        <DialogTitle>Удалить галерею?</DialogTitle>
                        <DialogDescription>
                            «{deleting?.title}» и все её фотографии будут
                            удалены. Это действие нельзя отменить.
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

GalleryIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Фотогалерея', href: index() },
    ],
};
