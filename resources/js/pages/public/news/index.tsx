import { Head, Link, router, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { Paginator } from '@/components/admin/data-table';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { show } from '@/routes/news';

type NewsCard = {
    title: string | null;
    slug: string | null;
    excerpt: string | null;
    category: string | null;
    cover_url: string | null;
    published_at: string | null;
};

type PageProps = {
    posts: Paginator<NewsCard> & {
        prev_page_url: string | null;
        next_page_url: string | null;
    };
};

export default function NewsIndex({ posts }: PageProps) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('news.title')} />

            <h1 className="mb-6 text-3xl font-semibold">{t('news.heading')}</h1>

            {posts.data.length === 0 ? (
                <p className="text-muted-foreground">
                    {t('common.no_publications')}
                </p>
            ) : (
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {posts.data.map((post) => (
                        <Link
                            key={post.slug}
                            href={show({ locale, slug: post.slug ?? '' }).url}
                            className="group flex flex-col overflow-hidden rounded-lg border transition-shadow hover:shadow-md"
                        >
                            <div className="aspect-video w-full bg-muted">
                                {post.cover_url && (
                                    <img
                                        src={post.cover_url}
                                        alt=""
                                        className="h-full w-full object-cover transition-transform group-hover:scale-105"
                                    />
                                )}
                            </div>
                            <div className="flex flex-1 flex-col gap-2 p-4">
                                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                    {post.category && (
                                        <span className="text-primary">
                                            {post.category}
                                        </span>
                                    )}
                                    {post.published_at && (
                                        <span>{post.published_at}</span>
                                    )}
                                </div>
                                <h2 className="leading-snug font-semibold group-hover:text-primary">
                                    {post.title}
                                </h2>
                                {post.excerpt && (
                                    <p className="line-clamp-3 text-sm text-muted-foreground">
                                        {post.excerpt}
                                    </p>
                                )}
                            </div>
                        </Link>
                    ))}
                </div>
            )}

            {(posts.prev_page_url || posts.next_page_url) && (
                <div className="mt-8 flex items-center justify-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!posts.prev_page_url}
                        onClick={() =>
                            posts.prev_page_url &&
                            router.get(posts.prev_page_url)
                        }
                    >
                        <ChevronLeft className="size-4" />
                        {t('common.back')}
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!posts.next_page_url}
                        onClick={() =>
                            posts.next_page_url &&
                            router.get(posts.next_page_url)
                        }
                    >
                        {t('common.next')}
                        <ChevronRight className="size-4" />
                    </Button>
                </div>
            )}
        </>
    );
}
