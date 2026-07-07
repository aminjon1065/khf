import { Link } from '@inertiajs/react';
import { ChevronDown, Mail, MapPin, Phone } from 'lucide-react';
import type { BlockData } from '@/components/admin/cp/blocks-field';
import { MapView } from '@/components/map-view';
import type { MapMarker } from '@/components/map-view';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export function BlockRenderer({
    blocks,
    latestPosts = [],
}: {
    blocks: BlockData[];
    latestPosts?: any[];
}) {
    if (!blocks || blocks.length === 0) return null;

    return (
        <div className="space-y-12">
            {blocks.map((block) => {
                switch (block.type) {
                    case 'text':
                        return (
                            <div
                                key={block.id}
                                className="prose prose-slate max-w-none prose-a:text-primary hover:prose-a:text-primary/80"
                                dangerouslySetInnerHTML={{ __html: block.data.content }}
                            />
                        );

                    case 'image_gallery': {
                        const images = (block.data.images ?? []).filter(
                            (image: { url?: string }) => image.url,
                        );

                        if (images.length === 0) return null;

                        return (
                            <div
                                key={block.id}
                                className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                {images.map(
                                    (
                                        image: {
                                            url: string;
                                            alt?: string;
                                            caption?: string;
                                        },
                                        index: number,
                                    ) => (
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
                                    ),
                                )}
                            </div>
                        );
                    }

                    case 'cta':
                        return (
                            <div key={block.id} className="flex justify-center py-8">
                                <Link
                                    href={block.data.url}
                                    className="inline-flex items-center justify-center rounded-lg bg-primary px-8 py-3 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                >
                                    {block.data.label}
                                </Link>
                            </div>
                        );

                    case 'news_list': {
                        const count = parseInt(block.data.count) || 6;
                        const posts = latestPosts.slice(0, count);

                        if (posts.length === 0) return null;

                        return (
                            <div
                                key={block.id}
                                className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                {posts.map((post) => (
                                    <Link
                                        key={post.slug}
                                        href={`/news/${post.slug}`}
                                        className="group flex flex-col overflow-hidden rounded-2xl border bg-card shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg"
                                    >
                                        <div className="relative aspect-[16/10] w-full overflow-hidden bg-muted">
                                            {post.cover_url && (
                                                <img
                                                    src={post.cover_url}
                                                    alt=""
                                                    className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                />
                                            )}
                                        </div>
                                        <div className="flex flex-1 flex-col p-6">
                                            <h3 className="text-xl font-bold text-foreground transition-colors group-hover:text-primary">
                                                {post.title}
                                            </h3>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        );
                    }

                    case 'map_widget': {
                        const lat = parseFloat(block.data.lat);
                        const lng = parseFloat(block.data.lng);
                        const zoom = parseInt(block.data.zoom) || 10;

                        if (Number.isNaN(lat) || Number.isNaN(lng)) return null;

                        const markers: MapMarker[] = [
                            {
                                id: block.id,
                                lat,
                                lng,
                                color: '#1f4e8c',
                                title: block.data.title || '',
                            },
                        ];

                        return (
                            <div
                                key={block.id}
                                className="overflow-hidden rounded-xl border bg-card shadow-sm"
                            >
                                <div className="aspect-[2/1] w-full">
                                    <MapView
                                        markers={markers}
                                        center={[lng, lat]}
                                        zoom={zoom}
                                    />
                                </div>
                            </div>
                        );
                    }

                    case 'accordion': {
                        const items = (block.data.items ?? []).filter(
                            (item: { title?: string }) => item.title,
                        );

                        if (items.length === 0) return null;

                        return (
                            <div key={block.id} className="space-y-3">
                                {items.map(
                                    (
                                        item: { title: string; content?: string },
                                        index: number,
                                    ) => (
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
                                                    dangerouslySetInnerHTML={{
                                                        __html: item.content,
                                                    }}
                                                />
                                            )}
                                        </details>
                                    ),
                                )}
                            </div>
                        );
                    }

                    case 'table': {
                        const headers: string[] = block.data.headers ?? [];
                        const rows: string[][] = block.data.rows ?? [];

                        if (headers.length === 0) return null;

                        return (
                            <div key={block.id} className="overflow-hidden rounded-xl border">
                                <Table>
                                    {block.data.caption && (
                                        <caption className="caption-top border-b bg-muted/40 px-4 py-3 text-left font-medium">
                                            {block.data.caption}
                                        </caption>
                                    )}
                                    <TableHeader>
                                        <TableRow>
                                            {headers.map((header, index) => (
                                                <TableHead key={index}>
                                                    {header}
                                                </TableHead>
                                            ))}
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {rows.map((row, rowIndex) => (
                                            <TableRow key={rowIndex}>
                                                {headers.map((_, colIndex) => (
                                                    <TableCell key={colIndex}>
                                                        {row[colIndex] ?? ''}
                                                    </TableCell>
                                                ))}
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        );
                    }

                    case 'contacts': {
                        const hasContent =
                            block.data.heading ||
                            block.data.address ||
                            block.data.phone ||
                            block.data.email ||
                            block.data.hours;

                        if (!hasContent) return null;

                        return (
                            <div
                                key={block.id}
                                className="rounded-xl border bg-card p-6 shadow-sm"
                            >
                                {block.data.heading && (
                                    <h3 className="text-lg font-semibold">
                                        {block.data.heading}
                                    </h3>
                                )}
                                <dl className="mt-4 space-y-3 text-sm">
                                    {block.data.address && (
                                        <div className="flex gap-3">
                                            <MapPin className="mt-0.5 size-4 shrink-0 text-primary" />
                                            <dd>{block.data.address}</dd>
                                        </div>
                                    )}
                                    {block.data.phone && (
                                        <div className="flex gap-3">
                                            <Phone className="mt-0.5 size-4 shrink-0 text-primary" />
                                            <dd>
                                                <a
                                                    href={`tel:${block.data.phone.replace(/\s/g, '')}`}
                                                    className="text-primary hover:underline"
                                                >
                                                    {block.data.phone}
                                                </a>
                                            </dd>
                                        </div>
                                    )}
                                    {block.data.email && (
                                        <div className="flex gap-3">
                                            <Mail className="mt-0.5 size-4 shrink-0 text-primary" />
                                            <dd>
                                                <a
                                                    href={`mailto:${block.data.email}`}
                                                    className="text-primary hover:underline"
                                                >
                                                    {block.data.email}
                                                </a>
                                            </dd>
                                        </div>
                                    )}
                                    {block.data.hours && (
                                        <div className="pt-1 text-muted-foreground">
                                            {block.data.hours}
                                        </div>
                                    )}
                                </dl>
                            </div>
                        );
                    }

                    default:
                        return (
                            <div
                                key={block.id}
                                className="rounded border border-dashed p-4 text-center text-muted-foreground"
                            >
                                Unknown block type: {block.type}
                            </div>
                        );
                }
            })}
        </div>
    );
}
