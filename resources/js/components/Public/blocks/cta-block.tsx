import { Link } from '@inertiajs/react';
import type { BlockComponentProps } from '@/components/Public/blocks/types';

export function CtaBlock({ block }: BlockComponentProps) {
    if (!block.data.label || !block.data.url) {
        return null;
    }

    return (
        <div className="flex justify-center py-8">
            <Link
                href={block.data.url}
                className="inline-flex items-center justify-center rounded-lg bg-primary px-8 py-3 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            >
                {block.data.label}
            </Link>
        </div>
    );
}
