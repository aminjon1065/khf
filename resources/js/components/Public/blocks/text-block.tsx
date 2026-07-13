import type { BlockComponentProps } from '@/components/Public/blocks/types';

export function TextBlock({ block }: BlockComponentProps) {
    if (!block.data.content) {
        return null;
    }

    return (
        <div
            className="prose prose-slate prose-a:text-primary hover:prose-a:text-primary/80 max-w-none"
            dangerouslySetInnerHTML={{ __html: block.data.content }}
        />
    );
}
