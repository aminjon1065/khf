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
import { create, destroy, edit, index } from '@/routes/admin/statistics';

type StatisticRow = {
    id: number;
    label: string;
    value: string;
    year: number | null;
    status: string;
    status_label: string;
    locales: string[];
    sort_order: number;
};

type PageProps = {
    statistics: Paginator<StatisticRow>;
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

export default function StatisticsIndex({ statistics, filters }: PageProps) {
    const [deleting, setDeleting] = useState<StatisticRow | null>(null);

    const columns: DataTableColumn<StatisticRow>[] = [
        {
            key: 'label',
            label: 'Показатель',
            render: (statistic) => (
                <div>
                    <span className="font-medium">{statistic.label}</span>
                    <span className="block text-xs text-muted-foreground">
                        {statistic.value}
                        {statistic.year ? ` · ${statistic.year}` : ''}
                    </span>
                </div>
            ),
        },
        {
            key: 'locales',
            label: 'Языки',
            render: (statistic) => (
                <div className="flex gap-1">
                    {['tj', 'ru', 'en'].map((locale) => {
                        const hasTranslation =
                            statistic.locales.includes(locale);

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
            render: (statistic) => (
                <Badge variant={statusVariant[statistic.status] ?? 'secondary'}>
                    {statistic.status_label}
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
            <Head title="Статистика" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Статистика</h1>
                    <p className="text-sm text-muted-foreground">
                        Основные показатели деятельности
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    paginator={statistics}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(statistic) => statistic.id}
                    searchPlaceholder="Поиск по показателю…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Добавить показатель
                            </Link>
                        </Button>
                    }
                    actions={(statistic) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                asChild
                            >
                                <Link href={edit(statistic.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                onClick={() => setDeleting(statistic)}
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
                        <DialogTitle>Удалить показатель?</DialogTitle>
                        <DialogDescription>
                            «{deleting?.label}» будет удалён. Это действие
                            нельзя отменить.
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

StatisticsIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Статистика', href: index() },
    ],
};
