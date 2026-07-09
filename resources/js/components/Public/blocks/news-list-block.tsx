import { Link } from '@inertiajs/react';
import type { BlockComponentProps } from '@/components/Public/blocks/types';

export function NewsListBlock({ block, context }: BlockComponentProps) {
    const count = parseInt(block.data.count, 10) || 6;
    const posts = context.latestPosts.slice(0, count);

    if (posts.length === 0) {
        return null;
    }

    return (
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
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
