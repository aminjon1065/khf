import { Head, Link, router } from '@inertiajs/react';
import { Check, ClipboardCheck, Pencil } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes/admin';
import { index as moderationIndex, publish } from '@/routes/admin/moderation';

type ModerationItem = {
    id: number;
    content_type: string;
    content_type_label: string;
    title: string;
    edit_url: string;
    updated_at: string | null;
};

type PageProps = {
    items: ModerationItem[];
    total: number;
    canPublish: boolean;
};

export default function ModerationIndex({
    items,
    total,
    canPublish,
}: PageProps) {
    const publishItem = (item: ModerationItem) => {
        router.post(
            publish({ type: item.content_type, id: item.id }).url,
            {},
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Очередь модерации" />

            <div className="space-y-6 p-4 sm:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Очередь модерации
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Материалы, ожидающие проверки перед публикацией.
                        </p>
                    </div>
                    <Badge variant="outline" className="gap-1">
                        <ClipboardCheck className="size-3.5" />
                        {total} в очереди
                    </Badge>
                </div>

                {items.length === 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>Очередь пуста</CardTitle>
                            <CardDescription>
                                Нет материалов со статусом «На модерации».
                            </CardDescription>
                        </CardHeader>
                    </Card>
                ) : (
                    <div className="space-y-3">
                        {items.map((item) => (
                            <Card key={`${item.content_type}-${item.id}`}>
                                <CardContent className="flex flex-wrap items-center justify-between gap-4 py-4">
                                    <div className="min-w-0 space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge variant="secondary">
                                                {item.content_type_label}
                                            </Badge>
                                            {item.updated_at && (
                                                <span className="text-xs text-muted-foreground">
                                                    {new Date(
                                                        item.updated_at,
                                                    ).toLocaleString('ru-RU')}
                                                </span>
                                            )}
                                        </div>
                                        <p className="truncate font-medium">
                                            {item.title}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        {canPublish && (
                                            <Button
                                                type="button"
                                                size="sm"
                                                onClick={() =>
                                                    publishItem(item)
                                                }
                                            >
                                                <Check className="mr-2 size-4" />
                                                Опубликовать
                                            </Button>
                                        )}
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link href={item.edit_url}>
                                                <Pencil className="mr-2 size-4" />
                                                Редактировать
                                            </Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

ModerationIndex.layout = {
    breadcrumbs: [
        { title: 'Панель управления', href: dashboard() },
        { title: 'Модерация', href: moderationIndex() },
    ],
};
