import { useEffect, useState } from 'react';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { Loader2, History } from 'lucide-react';

interface Revision {
    id: number;
    created_at: string;
    user: { id: number; name: string } | null;
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
    const { post, processing } = useForm();

    useEffect(() => {
        if (!open || !modelId) return;

        setLoading(true);
        fetch(`/admin/revisions/${modelType}/${modelId}`)
            .then((res) => res.json())
            .then((data) => setRevisions(data))
            .catch(() => setRevisions([]))
            .finally(() => setLoading(false));
    }, [open, modelType, modelId]);

    const restore = (id: number) => {
        if (!confirm('Вы уверены, что хотите восстановить эту версию? Текущие несохраненные изменения будут потеряны.')) {
            return;
        }

        post(`/admin/revisions/${id}/restore`, {
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="flex w-full flex-col sm:max-w-md">
                <SheetHeader>
                    <SheetTitle className="flex items-center gap-2">
                        <History className="size-5" />
                        История версий
                    </SheetTitle>
                    <SheetDescription>
                        Здесь отображаются все сохраненные версии этого материала. Вы можете восстановить любую из них.
                    </SheetDescription>
                </SheetHeader>

                <div className="mt-6 flex-1 overflow-y-auto pr-4 -mr-4">
                    {!modelId ? (
                        <p className="text-sm text-muted-foreground">Сохраните материал, чтобы появилась история версий.</p>
                    ) : loading ? (
                        <div className="flex h-32 items-center justify-center">
                            <Loader2 className="size-6 animate-spin text-muted-foreground" />
                        </div>
                    ) : revisions.length === 0 ? (
                        <p className="text-sm text-muted-foreground">История версий пуста.</p>
                    ) : (
                        <div className="space-y-4">
                            {revisions.map((rev, index) => {
                                const d = new Date(rev.created_at);
                                const dateStr = d.toLocaleDateString('ru-RU') + ' ' + d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
                                
                                return (
                                    <div
                                        key={rev.id}
                                        className="flex items-start justify-between gap-4 rounded-lg border border-border p-4"
                                    >
                                        <div>
                                            <p className="font-medium text-sm">{dateStr}</p>
                                            <p className="text-xs text-muted-foreground mt-1">
                                                {rev.user ? rev.user.name : 'Система'}
                                                {index === 0 && ' (Текущая версия)'}
                                            </p>
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={processing || index === 0}
                                            onClick={() => restore(rev.id)}
                                        >
                                            Восстановить
                                        </Button>
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
