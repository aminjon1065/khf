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
import { create, destroy, edit, index, trash } from '@/routes/admin/guides';

type GuideRow = {
    id: number;
    title: string;
    hazard_type: string | null;
    hazard_label: string | null;
    audience: string;
    audience_label: string;
    status: string;
    status_label: string;
    locales: string[];
    files_count: number;
    deleted_at: string | null;
};

type PageProps = {
    guides: Paginator<GuideRow>;
    filters: DataTableFilters;
    trashedCount: number;
};

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    published: 'default',
    draft: 'secondary',
    archived: 'outline',
};

const portalLocales = ['tj', 'ru', 'en'] as const;

export default function GuidesIndex({
    guides,
    filters,
    trashedCount,
}: PageProps) {
    const [deleting, setDeleting] = useState<GuideRow | null>(null);

    const columns: DataTableColumn<GuideRow>[] = [
        { key: 'title', label: 'Памятки' },
        {
            key: 'hazard_label',
            label: 'Тип ЧС',
            className: 'hidden md:table-cell',
            render: (guide) => guide.hazard_label ?? '—',
        },
        {
            key: 'audience_label',
            label: 'Аудитория',
            className: 'hidden md:table-cell',
        },
        {
            key: 'locales',
            label: 'Языки',
            render: (guide) => (
                <div className="flex gap-1">
                    {portalLocales.map((locale) => (
                        <Badge
                            key={locale}
                            variant={
                                guide.locales.includes(locale)
                                    ? 'default'
                                    : 'outline'
                            }
                            className={`uppercase ${guide.locales.includes(locale) ? '' : 'text-muted-foreground'}`}
                        >
                            {locale}
                        </Badge>
                    ))}
                </div>
            ),
        },
        {
            key: 'status',
            label: 'Статус',
            render: (guide) => (
                <Badge variant={statusVariant[guide.status] ?? 'secondary'}>
                    {guide.status_label}
                </Badge>
            ),
        },
        {
            key: 'files_count',
            label: 'Файлы',
            render: (guide) => String(guide.files_count),
        },
    ];

    return (
        <>
            <Head title="Памятки по безопасности" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Памятки по безопасности
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Реестр нормативных и ведомственных документов
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
                    paginator={guides}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(guide) => guide.id}
                    searchPlaceholder="Поиск по наименованию…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Создать
                            </Link>
                        </Button>
                    }
                    actions={(guide) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                asChild
                            >
                                <Link href={edit(guide.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                onClick={() => setDeleting(guide)}
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
                        <DialogTitle>Удалить документ?</DialogTitle>
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

GuidesIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Памятки', href: index() },
    ],
};
