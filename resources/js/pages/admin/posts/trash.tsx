import { Head, Link, router } from '@inertiajs/react';
import { ArchiveRestore, ArrowLeft, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type {Paginator} from '@/components/admin/data-table';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes/admin';
import { forceDelete, index, restore, trash } from '@/routes/admin/posts';

type PostRow = {
    id: number;
    title: string;
    type_label: string;
    status_label: string;
    deleted_at: string | null;
};

type PageProps = {
    posts: Paginator<PostRow>;
};

export default function PostsTrash({ posts }: PageProps) {
    const [purging, setPurging] = useState<PostRow | null>(null);

    return (
        <>
            <Head title="Корзина — материалы" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Корзина</h1>
                        <p className="text-sm text-muted-foreground">Удалённые материалы можно восстановить</p>
                    </div>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="size-4" />
                            К материалам
                        </Link>
                    </Button>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Заголовок</TableHead>
                                <TableHead>Тип</TableHead>
                                <TableHead className="hidden sm:table-cell">Удалён</TableHead>
                                <TableHead className="w-0 text-right">Действия</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {posts.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={4} className="h-24 text-center text-muted-foreground">
                                        Корзина пуста
                                    </TableCell>
                                </TableRow>
                            ) : (
                                posts.data.map((post) => (
                                    <TableRow key={post.id}>
                                        <TableCell>{post.title}</TableCell>
                                        <TableCell>{post.type_label}</TableCell>
                                        <TableCell className="hidden sm:table-cell">{post.deleted_at}</TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label="Восстановить"
                                                    onClick={() =>
                                                        router.patch(restore(post.id).url, {}, { preserveScroll: true })
                                                    }
                                                >
                                                    <ArchiveRestore className="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label="Удалить навсегда"
                                                    onClick={() => setPurging(post)}
                                                >
                                                    <Trash2 className="size-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <Dialog open={Boolean(purging)} onOpenChange={(open) => !open && setPurging(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Удалить навсегда?</DialogTitle>
                        <DialogDescription>
                            «{purging?.title}» будет удалён безвозвратно.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setPurging(null)}>
                            Отмена
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => {
                                if (purging) {
                                    router.delete(forceDelete(purging.id).url, {
                                        preserveScroll: true,
                                        onSuccess: () => setPurging(null),
                                    });
                                }
                            }}
                        >
                            Удалить навсегда
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

PostsTrash.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Новости и материалы', href: index() },
        { title: 'Корзина', href: trash() },
    ],
};
