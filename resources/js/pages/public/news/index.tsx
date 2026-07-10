import { Head, Link, router, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { Paginator } from '@/components/admin/data-table';
import { EmptyState } from '@/components/Public/empty-state';
import { NewsCover } from '@/components/Public/news-cover';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/utils';
import { index as newsIndex, show } from '@/routes/news';

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
    categories: Array<{ id: number; name: string }>;
    filters: {
        category_id: string | null;
    };
};

export default function NewsIndex({ posts, categories, filters }: PageProps) {
    const { locale } = usePage().props as { locale: string };
    const { t } = useTranslations();

    const handleCategoryFilter = (categoryId: number | null) => {
        router.get(
            newsIndex({ locale }).url,
            categoryId ? { category_id: categoryId } : {},
            { preserveState: true }
        );
    };

    return (
        <>
            <Head title={t('news.title')} />

            <h1 className="mb-6 text-3xl font-semibold">{t('news.heading')}</h1>

            {categories && categories.length > 0 && (
                <div className="mb-8 flex flex-wrap gap-2">
                    <Button
                        variant={!filters.category_id ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => handleCategoryFilter(null)}
                        className="rounded-full"
                    >
                        {t('common.all_categories')}
                    </Button>
                    {categories.map((category) => (
                        <Button
                            key={category.id}
                            variant={
                                filters.category_id == category.id.toString()
                                    ? 'default'
                                    : 'outline'
                            }
                            size="sm"
                            onClick={() => handleCategoryFilter(category.id)}
                            className="rounded-full"
                        >
                            {category.name}
                        </Button>
                    ))}
                </div>
            )}

            {posts.data.length === 0 ? (
                <EmptyState message={t('common.no_publications')} />
            ) : (
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {posts.data.map((post) => (
                        <Link
                            key={post.slug}
                            href={show({ locale, slug: post.slug ?? '' }).url}
                            className="group flex min-w-0 flex-col overflow-hidden rounded-lg border transition-shadow hover:shadow-md"
                        >
                            <NewsCover
                                src={post.cover_url}
                                locale={locale}
                                aspect="aspect-video"
                                imgClassName="transition-transform group-hover:scale-105"
                            />
                            <div className="flex flex-1 flex-col gap-2 p-4">
                                {post.category && (
                                    <span className="text-sm font-medium text-primary mb-2 block">
                                        {post.category}
                                    </span>
                                )}
                                <h3 className="text-xl font-bold leading-snug group-hover:underline">
                                    {post.title}
                                </h3>
                                <p className="mt-3 text-sm text-muted-foreground line-clamp-2">
                                    {post.excerpt}
                                </p>
                                <div className="mt-4 flex items-center gap-2 text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                    <span>
                                        {post.published_at
                                            ? formatDate(post.published_at, locale)
                                            : null}
                                    </span>
                                </div>
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
