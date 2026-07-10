import { AppEmblem } from '@/components/app-emblem';
import { cn } from '@/lib/utils';

type EmptyStateProps = {
    message: string;
    className?: string;
};

/**
 * Compact empty-state panel for public list pages.
 */
export function EmptyState({ message, className }: EmptyStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border bg-muted/30 px-6 py-10 text-center',
                className,
            )}
        >
            <AppEmblem className="size-10 text-muted-foreground/40" alt="" />
            <p className="max-w-sm text-sm text-muted-foreground">{message}</p>
        </div>
    );
}
