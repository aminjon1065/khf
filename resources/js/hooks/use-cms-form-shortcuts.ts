import { useEffect } from 'react';

type Options = {
    enabled?: boolean;
    onSave?: () => void;
    onPreview?: () => void;
};

/**
 * CMS publish-form shortcuts: ⌘/Ctrl+S to save, ⌘/Ctrl+P to open live preview.
 */
export function useCmsFormShortcuts({
    enabled = true,
    onSave,
    onPreview,
}: Options): void {
    useEffect(() => {
        if (!enabled) {
            return;
        }

        const handler = (event: KeyboardEvent) => {
            if (!event.metaKey && !event.ctrlKey) {
                return;
            }

            const key = event.key.toLowerCase();

            if (key === 's' && onSave) {
                event.preventDefault();
                onSave();
            }

            if (key === 'p' && onPreview) {
                event.preventDefault();
                onPreview();
            }
        };

        window.addEventListener('keydown', handler);

        return () => window.removeEventListener('keydown', handler);
    }, [enabled, onPreview, onSave]);
}
