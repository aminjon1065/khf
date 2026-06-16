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
import { create, destroy, edit, index } from '@/routes/admin/leadership';

type LeaderRow = {
    id: number;
    full_name: string;
    position: string | null;
    status: string;
    status_label: string;
    photo_url: string | null;
    locales: string[];
    sort_order: number;
};

type PageProps = {
    leaders: Paginator<LeaderRow>;
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

export default function LeadershipIndex({ leaders, filters }: PageProps) {
    const [deleting, setDeleting] = useState<LeaderRow | null>(null);

    const columns: DataTableColumn<LeaderRow>[] = [
        {
            key: 'full_name',
            label: 'ФИО',
            render: (leader) => (
                <div className="flex items-center gap-3">
                    {leader.photo_url ? (
                        <img
                            src={leader.photo_url}
                            alt=""
                            className="size-10 rounded-full object-cover"
                        />
                    ) : (
                        <div className="size-10 rounded-full bg-muted" />
                    )}
                    <div>
                        <span className="font-medium">{leader.full_name}</span>
                        {leader.position && (
                            <span className="block text-xs text-muted-foreground">
                                {leader.position}
                            </span>
                        )}
                    </div>
                </div>
            ),
        },
        {
            key: 'locales',
            label: 'Языки',
            render: (leader) => (
                <div className="flex gap-1">
                    {['tj', 'ru', 'en'].map((locale) => {
                        const hasTranslation = leader.locales.includes(locale);

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
            render: (leader) => (
                <Badge variant={statusVariant[leader.status] ?? 'secondary'}>
                    {leader.status_label}
                </Badge>
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
            <Head title="Руководство" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Руководство</h1>
                    <p className="text-sm text-muted-foreground">
                        Руководители и график приёма граждан
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    paginator={leaders}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(leader) => leader.id}
                    searchPlaceholder="Поиск по ФИО…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Добавить руководителя
                            </Link>
                        </Button>
                    }
                    actions={(leader) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                asChild
                            >
                                <Link href={edit(leader.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                onClick={() => setDeleting(leader)}
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
                        <DialogTitle>Удалить руководителя?</DialogTitle>
                        <DialogDescription>
                            «{deleting?.full_name}» будет удалён. Это действие
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

LeadershipIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Руководство', href: index() },
    ],
};
