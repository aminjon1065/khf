import { resolveBlockComponent } from '@/components/Public/blocks/registry';
import type { BlockRendererProps } from '@/components/Public/blocks/types';

export type { BlockRendererProps, LatestPostSummary } from '@/components/Public/blocks/types';

export function BlockRenderer({
    blocks,
    latestPosts = [],
}: BlockRendererProps) {
    if (!blocks || blocks.length === 0) {
        return null;
    }

    const context = { latestPosts };

    return (
        <div className="space-y-12">
            {blocks.map((block) => {
                const Component = resolveBlockComponent(block.type);

                return (
                    <Component
                        key={block.id}
                        block={block}
                        context={context}
                    />
                );
            })}
        </div>
    );
}
