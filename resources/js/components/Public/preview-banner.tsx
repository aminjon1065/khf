import { usePage } from '@inertiajs/react';
import { Eye } from 'lucide-react';

/**
 * Sticky banner shown when viewing CMS live preview (unpublished content).
 */
export function PreviewBanner() {
    const { isPreview } = usePage().props as { isPreview?: boolean };

    if (!isPreview) {
        return null;
    }

    return (
        <div
            className="sticky top-0 z-50 border-b border-amber-500/40 bg-amber-500 px-4 py-2 text-center text-sm font-medium text-amber-950"
            role="status"
        >
            <span className="inline-flex items-center gap-2">
                <Eye className="size-4 shrink-0" aria-hidden="true" />
                Режим предпросмотра — контент ещё не опубликован
            </span>
        </div>
    );
}
