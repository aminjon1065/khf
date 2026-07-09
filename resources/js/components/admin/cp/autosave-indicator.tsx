import { cn } from '@/lib/utils';
import type { AutosaveState } from '@/hooks/use-autosave';

const labels: Record<AutosaveState, string | null> = {
    idle: null,
    pending: 'Есть несохранённые изменения…',
    saving: 'Автосохранение…',
    saved: 'Черновик сохранён',
    error: 'Не удалось автосохранить',
};

export function CpAutosaveIndicator({
    state,
    savedAt,
}: {
    state: AutosaveState;
    savedAt: Date | null;
}) {
    const label = labels[state];

    if (!label) {
        return null;
    }

    const time =
        state === 'saved' && savedAt
            ? savedAt.toLocaleTimeString('ru-RU', {
                  hour: '2-digit',
                  minute: '2-digit',
              })
            : null;

    return (
        <p
            className={cn(
                'text-xs',
                state === 'error'
                    ? 'text-destructive'
                    : 'text-muted-foreground',
            )}
            role="status"
            aria-live="polite"
        >
            {label}
            {time ? ` (${time})` : ''}
        </p>
    );
}
