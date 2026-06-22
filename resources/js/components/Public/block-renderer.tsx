import { Link } from '@inertiajs/react';
import type { BlockData } from '@/components/admin/cp/blocks-field';

export function BlockRenderer({ blocks, latestPosts = [] }: { blocks: BlockData[], latestPosts?: any[] }) {
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

                    case 'news_list':
                        // If count is specified, slice the posts
                        const count = parseInt(block.data.count) || 6;
                        const posts = latestPosts.slice(0, count);
                        
                        if (posts.length === 0) return null;

                        return (
                            <div key={block.id} className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
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

                    case 'map_widget':
                        return (
                            <div key={block.id} className="rounded-xl border bg-card p-4 shadow-sm">
                                <div className="aspect-[2/1] w-full bg-muted flex items-center justify-center rounded-lg">
                                    <p className="text-muted-foreground">
                                        Map Widget (Lat: {block.data.lat}, Lng: {block.data.lng})
                                    </p>
                                </div>
                            </div>
                        );

                    default:
                        return (
                            <div key={block.id} className="p-4 border border-dashed rounded text-center text-muted-foreground">
                                Unknown block type: {block.type}
                            </div>
                        );
                }
            })}
        </div>
    );
}
