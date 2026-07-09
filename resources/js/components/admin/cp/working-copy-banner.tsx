import { router } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import { Button } from '@/components/ui/button';

/**
 * Prompts publishers to push working-copy edits to the live site.
 */
export function CpWorkingCopyBanner({
    hasUnpublishedChanges,
    canPublish,
    publishUrl,
}: {
    hasUnpublishedChanges: boolean;
    canPublish: boolean;
    publishUrl: string | null;
}) {
    if (!hasUnpublishedChanges || !canPublish || !publishUrl) {
        return null;
    }

    return (
        <div className="rounded-lg border border-amber-500/40 bg-amber-500/10 p-4 text-sm">
            <p className="font-medium text-amber-950 dark:text-amber-100">
                Есть неопубликованные изменения
            </p>
            <p className="mt-1 text-muted-foreground">
                На сайте показывается последняя опубликованная версия. Сохраните
                правки в рабочей копии и нажмите кнопку ниже, чтобы обновить
                публичную версию.
            </p>
            <Button
                type="button"
                size="sm"
                className="mt-3"
                onClick={() =>
                    router.post(publishUrl, {}, { preserveScroll: true })
                }
            >
                <Upload className="size-4" />
                Опубликовать изменения
            </Button>
        </div>
    );
}
