import { Clock, Eye, EyeOff } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type StatusOption = { value: string; label: string };

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    draft: 'secondary',
    moderation: 'outline',
    published: 'default',
    archived: 'destructive',
};

/**
 * CMS publication workflow: current status badge, allowed transition actions, and optional
 * publish/unpublish schedule fields (ТЗ §6.2, §7.2).
 */
export function CpContentPublishPanel({
    status,
    statuses,
    transitions,
    publishedAt = '',
    unpublishedAt = '',
    showSchedule = false,
    onStatusChange,
    onPublishedAtChange,
    onUnpublishedAtChange,
    errors,
}: {
    status: string;
    statuses: StatusOption[];
    transitions: StatusOption[];
    publishedAt?: string;
    unpublishedAt?: string;
    showSchedule?: boolean;
    onStatusChange: (status: string) => void;
    onPublishedAtChange?: (value: string) => void;
    onUnpublishedAtChange?: (value: string) => void;
    errors: Record<string, string | undefined>;
}) {
    const currentLabel =
        statuses.find((option) => option.value === status)?.label ?? status;

    const publishDate = publishedAt ? new Date(publishedAt) : null;
    const unpublishDate = unpublishedAt ? new Date(unpublishedAt) : null;
    const now = new Date();

    const isScheduled =
        status === 'published' &&
        publishDate !== null &&
        !Number.isNaN(publishDate.getTime()) &&
        publishDate > now;

    const isScheduledUnpublish =
        status === 'published' &&
        unpublishDate !== null &&
        !Number.isNaN(unpublishDate.getTime()) &&
        unpublishDate > now;

    return (
        <div className="space-y-4">
            <div className="space-y-2">
                <Label>Текущий статус</Label>
                <div className="flex flex-wrap items-center gap-2">
                    <Badge variant={statusVariant[status] ?? 'secondary'}>
                        {currentLabel}
                    </Badge>
                    {isScheduled && (
                        <Badge variant="outline" className="gap-1">
                            <Clock className="size-3" />
                            Запланировано
                        </Badge>
                    )}
                    {isScheduledUnpublish && (
                        <Badge variant="outline" className="gap-1">
                            <EyeOff className="size-3" />
                            Снятие запланировано
                        </Badge>
                    )}
                    {status === 'published' &&
                        !isScheduled &&
                        !isScheduledUnpublish && (
                            <Badge variant="outline" className="gap-1">
                                <Eye className="size-3" />
                                На сайте
                            </Badge>
                        )}
                </div>
                <InputError message={errors.status} />
            </div>

            {transitions.length > 0 && (
                <div className="space-y-2">
                    <Label>Действия</Label>
                    <div className="flex flex-wrap gap-2">
                        {transitions.map((transition) => (
                            <Button
                                key={transition.value}
                                type="button"
                                size="sm"
                                variant="outline"
                                className={cn(
                                    status === transition.value &&
                                        'pointer-events-none opacity-50',
                                )}
                                onClick={() =>
                                    onStatusChange(transition.value)
                                }
                            >
                                {transition.label}
                            </Button>
                        ))}
                    </div>
                </div>
            )}

            {showSchedule && status === 'published' && (
                <div className="space-y-3 border-t border-border pt-3">
                    <p className="text-xs text-muted-foreground">
                        Опубликованный материал появится на сайте после даты
                        публикации и скроется после даты снятия (если указана).
                    </p>
                    <div className="space-y-2">
                        <Label htmlFor="published_at">Дата публикации</Label>
                        <Input
                            id="published_at"
                            type="datetime-local"
                            value={publishedAt}
                            onChange={(event) =>
                                onPublishedAtChange?.(event.target.value)
                            }
                        />
                        <InputError message={errors.published_at} />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="unpublished_at">
                            Снять с публикации
                        </Label>
                        <Input
                            id="unpublished_at"
                            type="datetime-local"
                            value={unpublishedAt}
                            onChange={(event) =>
                                onUnpublishedAtChange?.(event.target.value)
                            }
                        />
                        <InputError message={errors.unpublished_at} />
                    </div>
                </div>
            )}
        </div>
    );
}
