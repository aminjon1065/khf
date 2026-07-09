import { Info } from 'lucide-react';

/**
 * Short contextual help for CMS editors — keeps complex content models understandable.
 */
export function CpContentHelp({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <div className="rounded-lg border border-primary/20 bg-primary/5 p-4 text-sm">
            <div className="mb-2 flex items-center gap-2 font-medium text-foreground">
                <Info className="size-4 shrink-0 text-primary" aria-hidden />
                {title}
            </div>
            <div className="space-y-2 text-muted-foreground">{children}</div>
        </div>
    );
}
