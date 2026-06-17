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
import { create, destroy, edit, index } from '@/routes/admin/faqs';

type FaqRow = {
    id: number;
    question: string;
    status: string;
    status_label: string;
    locales: string[];
    sort_order: number;
};

type PageProps = {
    faqs: Paginator<FaqRow>;
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

export default function FaqIndex({ faqs, filters }: PageProps) {
    const [deleting, setDeleting] = useState<FaqRow | null>(null);

    const columns: DataTableColumn<FaqRow>[] = [
        { key: 'question', label: 'Вопрос' },
        {
            key: 'locales',
            label: 'Языки',
            render: (faq) => (
                <div className="flex gap-1">
                    {['tj', 'ru', 'en'].map((locale) => {
                        const hasTranslation = faq.locales.includes(locale);

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
            render: (faq) => (
                <Badge variant={statusVariant[faq.status] ?? 'secondary'}>
                    {faq.status_label}
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
            <Head title="Вопросы и ответы" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Вопросы и ответы</h1>
                    <p className="text-sm text-muted-foreground">
                        Часто задаваемые вопросы граждан
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    paginator={faqs}
                    filters={filters}
                    baseUrl={index().url}
                    getRowId={(faq) => faq.id}
                    searchPlaceholder="Поиск по вопросу…"
                    toolbar={
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="size-4" />
                                Добавить вопрос
                            </Link>
                        </Button>
                    }
                    actions={(faq) => (
                        <div className="flex justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Изменить"
                                asChild
                            >
                                <Link href={edit(faq.id).url}>
                                    <Pencil className="size-4" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Удалить"
                                onClick={() => setDeleting(faq)}
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
                        <DialogTitle>Удалить вопрос?</DialogTitle>
                        <DialogDescription>
                            «{deleting?.question}» будет удалён. Это действие
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

FaqIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Вопросы и ответы', href: index() },
    ],
};
