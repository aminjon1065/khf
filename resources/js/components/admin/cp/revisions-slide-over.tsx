import { useForm } from '@inertiajs/react';
import { ChevronDown, ChevronUp, Loader2, History } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';

interface Revision {
    id: number;
    created_at: string;
    user: { id: number; name: string } | null;
}

interface RevisionChange {
    group: string;
    locale: string | null;
    field: string;
    label: string;
    before: string;
    after: string;
}

interface RevisionDetail {
    revision: Revision;
    compare_label: string;
    changes: RevisionChange[];
}

export function RevisionsSlideOver({
    open,
    onOpenChange,
    modelType,
    modelId,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    modelType: string;
    modelId: number | null;
}) {
    const [revisions, setRevisions] = useState<Revision[]>([]);
    const [loading, setLoading] = useState(false);
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [detail, setDetail] = useState<RevisionDetail | null>(null);
    const [detailLoading, setDetailLoading] = useState(false);
    const { post, processing } = useForm();

    useEffect(() => {
        if (!open || !modelId) {
            return;
        }

        setLoading(true);
        setSelectedId(null);
        setDetail(null);

        fetch(`/admin/revisions/${modelType}/${modelId}`)
            .then((res) => res.json())
            .then((data) => setRevisions(data))
            .catch(() => setRevisions([]))
            .finally(() => setLoading(false));
    }, [open, modelType, modelId]);

    useEffect(() => {
        if (!selectedId) {
            setDetail(null);

            return;
        }

        setDetailLoading(true);

        fetch(`/admin/revisions/detail/${selectedId}`)
            .then((res) => res.json())
            .then((data) => setDetail(data))
            .catch(() => setDetail(null))
            .finally(() => setDetailLoading(false));
    }, [selectedId]);

    const restore = (id: number) => {
        if (
            !confirm(
                'Вы уверены, что хотите восстановить эту версию? Текущие несохраненные изменения будут потеряны.',
            )
        ) {
            return;
        }

        post(`/admin/revisions/${id}/restore`, {
            onSuccess: () => onOpenChange(false),
        });
    };

    const toggleDetail = (id: number) => {
        setSelectedId((current) => (current === id ? null : id));
    };

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="flex w-full flex-col sm:max-w-2xl">
                <SheetHeader>
                    <SheetTitle className="flex items-center gap-2">
                        <History className="size-5" />
                        История версий
                    </SheetTitle>
                    <SheetDescription>
                        Просматривайте изменения между версиями и
                        восстанавливайте нужную.
                    </SheetDescription>
                </SheetHeader>

                <div className="mt-6 -mr-4 flex-1 overflow-y-auto pr-4">
                    {!modelId ? (
                        <p className="text-sm text-muted-foreground">
                            Сохраните материал, чтобы появилась история версий.
                        </p>
                    ) : loading ? (
                        <div className="flex h-32 items-center justify-center">
                            <Loader2 className="size-6 animate-spin text-muted-foreground" />
                        </div>
                    ) : revisions.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            История версий пуста.
                        </p>
                    ) : (
                        <div className="space-y-4">
                            {revisions.map((rev, index) => {
                                const d = new Date(rev.created_at);
                                const dateStr =
                                    d.toLocaleDateString('ru-RU') +
                                    ' ' +
                                    d.toLocaleTimeString('ru-RU', {
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    });
                                const isSelected = selectedId === rev.id;
                                const isLatest = index === 0;

                                return (
                                    <div
                                        key={rev.id}
                                        className="rounded-lg border border-border"
                                    >
                                        <div className="flex items-start justify-between gap-4 p-4">
                                            <button
                                                type="button"
                                                className="min-w-0 flex-1 text-left"
                                                onClick={() =>
                                                    toggleDetail(rev.id)
                                                }
                                            >
                                                <div className="flex items-center gap-2">
                                                    <p className="text-sm font-medium">
                                                        {dateStr}
                                                    </p>
                                                    {isSelected ? (
                                                        <ChevronUp className="size-4 text-muted-foreground" />
                                                    ) : (
                                                        <ChevronDown className="size-4 text-muted-foreground" />
                                                    )}
                                                </div>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {rev.user
                                                        ? rev.user.name
                                                        : 'Система'}
                                                    {isLatest &&
                                                        ' (Последняя версия)'}
                                                </p>
                                            </button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                disabled={
                                                    processing || isLatest
                                                }
                                                onClick={() => restore(rev.id)}
                                            >
                                                Восстановить
                                            </Button>
                                        </div>

                                        {isSelected && (
                                            <div className="border-t border-border bg-muted/30 p-4">
                                                {detailLoading ? (
                                                    <div className="flex h-20 items-center justify-center">
                                                        <Loader2 className="size-5 animate-spin text-muted-foreground" />
                                                    </div>
                                                ) : detail &&
                                                  detail.changes.length > 0 ? (
                                                    <div className="space-y-4">
                                                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                            Изменения до:{' '}
                                                            {
                                                                detail.compare_label
                                                            }
                                                        </p>
                                                        {detail.changes.map(
                                                            (change) => (
                                                                <div
                                                                    key={`${change.group}-${change.locale ?? 'root'}-${change.field}`}
                                                                    className="space-y-2"
                                                                >
                                                                    <p className="text-sm font-medium">
                                                                        {
                                                                            change.label
                                                                        }
                                                                        {change.locale && (
                                                                            <span className="ml-2 font-mono text-xs text-muted-foreground">
                                                                                {
                                                                                    change.locale
                                                                                }
                                                                            </span>
                                                                        )}
                                                                    </p>
                                                                    <div className="grid gap-2 sm:grid-cols-2">
                                                                        <div className="rounded-md border border-border bg-background p-3">
                                                                            <p className="mb-1 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                                                                                Было
                                                                            </p>
                                                                            <p className="text-sm break-words whitespace-pre-wrap text-muted-foreground">
                                                                                {
                                                                                    change.before
                                                                                }
                                                                            </p>
                                                                        </div>
                                                                        <div className="rounded-md border border-primary/20 bg-primary/5 p-3">
                                                                            <p className="mb-1 text-[10px] font-semibold tracking-wide text-primary uppercase">
                                                                                Стало
                                                                            </p>
                                                                            <p className="text-sm break-words whitespace-pre-wrap">
                                                                                {
                                                                                    change.after
                                                                                }
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            ),
                                                        )}
                                                    </div>
                                                ) : (
                                                    <p className="text-sm text-muted-foreground">
                                                        {isLatest
                                                            ? 'Это последняя сохранённая версия.'
                                                            : 'Изменений по сравнению со следующей версией не найдено.'}
                                                    </p>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}
