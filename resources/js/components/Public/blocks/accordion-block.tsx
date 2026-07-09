import { ChevronDown } from 'lucide-react';
import type { BlockComponentProps } from '@/components/Public/blocks/types';

type AccordionItem = {
    title: string;
    content?: string;
};

export function AccordionBlock({ block }: BlockComponentProps) {
    const items = (block.data.items ?? []).filter(
        (item: { title?: string }) => item.title,
    ) as AccordionItem[];

    if (items.length === 0) {
        return null;
    }

    return (
        <div className="space-y-3">
            {items.map((item, index) => (
                <details
                    key={`${block.id}-${index}`}
                    className="group rounded-lg border p-4 [&_summary::-webkit-details-marker]:hidden"
                >
                    <summary className="flex cursor-pointer items-center justify-between gap-4 font-medium">
                        {item.title}
                        <ChevronDown className="size-5 shrink-0 text-muted-foreground transition-transform group-open:rotate-180" />
                    </summary>
                    {item.content && (
                        <div
                            className="rte-content mt-3 leading-relaxed text-muted-foreground"
                            dangerouslySetInnerHTML={{ __html: item.content }}
                        />
                    )}
                </details>
            ))}
        </div>
    );
}
