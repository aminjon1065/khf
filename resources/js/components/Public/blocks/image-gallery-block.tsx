import type { BlockComponentProps } from '@/components/Public/blocks/types';

type GalleryImage = {
    url: string;
    alt?: string;
    caption?: string;
};

export function ImageGalleryBlock({ block }: BlockComponentProps) {
    const images = (block.data.images ?? []).filter(
        (image: { url?: string }) => image.url,
    ) as GalleryImage[];

    if (images.length === 0) {
        return null;
    }

    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {images.map((image, index) => (
                <figure
                    key={`${block.id}-${index}`}
                    className="overflow-hidden rounded-xl border bg-card shadow-sm"
                >
                    <div className="aspect-[4/3] w-full overflow-hidden bg-muted">
                        <img
                            src={image.url}
                            alt={image.alt ?? ''}
                            className="h-full w-full object-cover"
                        />
                    </div>
                    {image.caption && (
                        <figcaption className="px-4 py-3 text-sm text-muted-foreground">
                            {image.caption}
                        </figcaption>
                    )}
                </figure>
            ))}
        </div>
    );
}
