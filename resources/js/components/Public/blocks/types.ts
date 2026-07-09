import type { BlockData } from '@/components/admin/cp/blocks-field';

export type LatestPostSummary = {
    slug: string;
    title: string;
    cover_url?: string | null;
};

export type BlockRendererContext = {
    latestPosts: LatestPostSummary[];
};

export type BlockComponentProps = {
    block: BlockData;
    context: BlockRendererContext;
};

export type BlockRendererProps = {
    blocks: BlockData[];
    latestPosts?: LatestPostSummary[];
};
