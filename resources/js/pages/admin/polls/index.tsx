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
import { create, destroy, edit, index } from '@/routes/admin/polls';

type PollRow = {
    id: number;
    title: string;
    type: string;
    type_label: string;
    status: string;
    status_label: string;
    votes_count: number;
    starts_at: string | null;
    ends_at: string | null;
    locales: string[];
};

type PageProps = {
    polls: Paginator<PollRow>;
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

export default function PollsIndex({ polls, filters }: PageProps) {
    const [deleting, setDeleting] = useState<PollRow | null>(null);

    const columns: DataTableColumn<PollRow>[] = [
        { key: 'title', label: 'Название' },
        {
            key: 'type',
            label: 'Тип',
            render: (poll) => (
                <Badge variant="outline">{poll.type_label}</Badge>
            ),
        },
        {
            key: 'status',
            label: 'Статус',
            render: (poll) => (
                <Badge variant={statusVariant[poll.status] ?? 'secondary'}>
                    {poll.status_label}
                </Badge>
            ),
        },
        {
            key: 'votes_count',
            label: 'Голосов',
            className: 'hidden sm:table-cell',
        },
        {
            key: 'locales',
            label: 'Языки',
            render: (poll) => (
                <div className="flex gap-1">
                    {['tj', 'ru', 'en'].map((locale) => {
                        const hasTranslation = poll.locales.includes(locale);

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
    ];

    return (
        <>
            <Head title="Опросы" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Опросы</h1>
                    <p className="text-sm text-muted-foreground">
                        Общественные опросы и антикоррупционная экспертиза
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    paginator={polls}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(poll) => poll.id}
                    searchPlaceholder="Поиск по названию…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Создать опрос
                            </Link>
                        </Button>
                    }
                    actions={(poll) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                asChild
                            >
                                <Link href={edit(poll.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                onClick={() => setDeleting(poll)}
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
                        <DialogTitle>Удалить опрос?</DialogTitle>
                        <DialogDescription>
                            «{deleting?.title}» будет удалён вместе со всеми
                            голосами. Это действие нельзя отменить.
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

PollsIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Опросы', href: index() },
    ],
};
