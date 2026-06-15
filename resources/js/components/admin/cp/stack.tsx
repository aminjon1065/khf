import type { ReactNode } from 'react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';

/**
 * Statamic-style "stack" — a slide-over panel anchored to the right edge, over a dimmed overlay,
 * for nested editing / relationship pickers / the asset browser. Built on the Radix Sheet, so it
 * gets focus-trapping, Esc-to-close and the slide animation for free; multiple stacks layer via
 * Radix's portal/overlay z-index.
 */
export function CpStack({
    open,
    onOpenChange,
    title,
    description,
    children,
    footer,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: string;
    children: ReactNode;
    footer?: ReactNode;
}) {
    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="right"
                className="flex w-full flex-col gap-0 p-0 sm:max-w-md"
            >
                <SheetHeader className="space-y-1 border-b border-border px-4 py-3 text-left">
                    <SheetTitle>{title}</SheetTitle>
                    {description ? (
                        <SheetDescription>{description}</SheetDescription>
                    ) : (
                        <SheetDescription className="sr-only">
                            {title}
                        </SheetDescription>
                    )}
                </SheetHeader>

                <div className="flex-1 overflow-y-auto p-4">{children}</div>

                {footer && (
                    <SheetFooter className="border-t border-border p-4">
                        {footer}
                    </SheetFooter>
                )}
            </SheetContent>
        </Sheet>
    );
}
