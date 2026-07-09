import type { ComponentType } from 'react';
import { AccordionBlock } from '@/components/Public/blocks/accordion-block';
import { ContactsBlock } from '@/components/Public/blocks/contacts-block';
import { CtaBlock } from '@/components/Public/blocks/cta-block';
import { ImageGalleryBlock } from '@/components/Public/blocks/image-gallery-block';
import { MapWidgetBlock } from '@/components/Public/blocks/map-widget-block';
import { NewsListBlock } from '@/components/Public/blocks/news-list-block';
import { TableBlock } from '@/components/Public/blocks/table-block';
import { TextBlock } from '@/components/Public/blocks/text-block';
import type { BlockComponentProps } from '@/components/Public/blocks/types';
import { UnknownBlock } from '@/components/Public/blocks/unknown-block';

export const blockRegistry = {
    text: TextBlock,
    image_gallery: ImageGalleryBlock,
    news_list: NewsListBlock,
    map_widget: MapWidgetBlock,
    cta: CtaBlock,
    accordion: AccordionBlock,
    table: TableBlock,
    contacts: ContactsBlock,
} as const satisfies Record<string, ComponentType<BlockComponentProps>>;

export type RegisteredBlockType = keyof typeof blockRegistry;

export function resolveBlockComponent(
    type: string,
): ComponentType<BlockComponentProps> {
    return blockRegistry[type as RegisteredBlockType] ?? UnknownBlock;
}

export function registeredBlockTypes(): RegisteredBlockType[] {
    return Object.keys(blockRegistry) as RegisteredBlockType[];
}
