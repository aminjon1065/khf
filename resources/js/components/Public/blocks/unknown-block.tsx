import type { BlockComponentProps } from '@/components/Public/blocks/types';

export function UnknownBlock({ block }: BlockComponentProps) {
    return (
        <div className="rounded border border-dashed p-4 text-center text-muted-foreground">
            Unknown block type: {block.type}
        </div>
    );
}
