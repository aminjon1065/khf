import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

export function AlertPublishConfirmDialog({
    open,
    onOpenChange,
    estimatedCount,
    isEstimating,
    isProcessing,
    onConfirm,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    estimatedCount: number | null;
    isEstimating: boolean;
    isProcessing: boolean;
    onConfirm: () => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Подтверждение публикации</DialogTitle>
                    <DialogDescription>
                        Вы собираетесь опубликовать это оповещение. После
                        публикации оно будет немедленно отправлено подписчикам
                        через Email и Push-уведомления. Отменить эту операцию
                        невозможно.
                    </DialogDescription>
                </DialogHeader>

                <div className="py-4">
                    <p className="text-sm font-medium">
                        Оценочное количество получателей:
                    </p>
                    <p className="mt-1 text-3xl font-bold text-primary">
                        {isEstimating ? '...' : (estimatedCount ?? 0)}
                    </p>
                    <p className="mt-2 text-xs text-muted-foreground">
                        *Учитываются подтвержденные подписчики, выбравшие тему
                        «Оповещения» и подходящие по региону.
                    </p>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        disabled={isProcessing}
                    >
                        Отмена
                    </Button>
                    <Button
                        type="button"
                        onClick={onConfirm}
                        disabled={isEstimating || isProcessing}
                    >
                        {isProcessing
                            ? 'Публикация...'
                            : 'Опубликовать и отправить'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
