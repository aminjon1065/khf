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
import { create, destroy, edit, index, trash } from '@/routes/admin/posts';

type PostRow = {
    id: number;
    title: string;
    type: string;
    type_label: string;
    category: string | null;
    status: string;
    status_label: string;
    cover_url: string | null;
    locales: string[];
    published_at: string | null;
};

type PageProps = {
    posts: Paginator<PostRow>;
    filters: DataTableFilters;
    trashedCount: number;
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

export default function PostsIndex({
    posts,
    filters,
    trashedCount,
}: PageProps) {
    const [deleting, setDeleting] = useState<PostRow | null>(null);

    const columns: DataTableColumn<PostRow>[] = [
        {
            key: 'title',
            label: 'Заголовок',
            render: (post) => (
                <div className="flex items-center gap-3">
                    {post.cover_url ? (
                        <img
                            src={post.cover_url}
                            alt=""
                            className="h-10 w-16 rounded object-cover"
                        />
                    ) : (
                        <div className="h-10 w-16 rounded bg-muted" />
                    )}
                    <span>{post.title}</span>
                </div>
            ),
        },
        {
            key: 'locales',
            label: 'Языки',
            render: (post) => (
                <div className="flex gap-1">
                    {['tj', 'ru', 'en'].map((locale) => {
                        const hasTranslation = post.locales.includes(locale);

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
            key: 'type',
            label: 'Тип',
            sortable: true,
            render: (post) => post.type_label,
        },
        {
            key: 'category',
            label: 'Рубрика',
            className: 'hidden md:table-cell',
            render: (post) => post.category ?? '—',
        },
        {
            key: 'status',
            label: 'Статус',
            sortable: true,
            render: (post) => (
                <Badge variant={statusVariant[post.status] ?? 'secondary'}>
                    {post.status_label}
                </Badge>
            ),
        },
        {
            key: 'published_at',
            label: 'Публикация',
            sortable: true,
            className: 'hidden sm:table-cell',
        },
    ];

    return (
        <>
            <Head title="Новости и материалы" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Новости и материалы
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Новости, пресс-релизы, объявления и оперативные
                            сводки
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
                    paginator={posts}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(post) => post.id}
                    searchPlaceholder="Поиск по заголовку…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Создать материал
                            </Link>
                        </Button>
                    }
                    actions={(post) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                asChild
                            >
                                <Link href={edit(post.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                onClick={() => setDeleting(post)}
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
                        <DialogTitle>Удалить материал?</DialogTitle>
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

PostsIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Новости и материалы', href: index() },
    ],
};
