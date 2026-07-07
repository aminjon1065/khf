import { Head, Link, usePage } from '@inertiajs/react';
import { Paperclip, FileIcon } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';
import { index as newsIndex, show } from '@/routes/news';
import { MissingTranslationAlert } from '@/components/Public/missing-translation-alert';
import { formatDate } from '@/lib/utils';

type MediaItem = {
    url: string;
    thumb?: string;
    name?: string;
    size?: string;
    ext?: string;
};

type Article = {
    title: string;
    excerpt: string | null;
    body: string | null;
    type_label: string;
    category: string | null;
    tags: string[];
    cover_url: string | null;
    author: string | null;
    published_at: string | null;
    gallery: MediaItem[];
    attachments: MediaItem[];
};

type RelatedItem = {
    title: string | null;
    slug: string | null;
    published_at: string | null;
};

type PageProps = {
    post: Article;
    related: RelatedItem[];
};

export default function NewsShow({ post, related }: PageProps) {
    const { locale } = usePage().props as { locale: string };
    const { t } = useTranslations();

    return (
        <>
            <Head title={post.title} />

            {post.locale && <MissingTranslationAlert contentLocale={post.locale} />}

            <div className="grid gap-10 lg:grid-cols-[1fr_320px]">
                <article className="min-w-0">
                    <Link
                        href={newsIndex({ locale }).url}
                        className="text-sm text-primary hover:underline"
                    >
                        {t('news.back_to_list')}
                    </Link>

                    <div className="mt-3 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                        <span className="text-primary">
                            {post.category ?? post.type_label}
                        </span>
                        {post.published_at && (
                            <span>· {formatDate(post.published_at, locale)}</span>
                        )}
                    </div>

                    {post.tags.length > 0 && (
                        <div className="mt-2 flex flex-wrap gap-1.5">
                            {post.tags.map((tag) => (
                                <span
                                    key={tag}
                                    className="rounded-full bg-muted px-2.5 py-0.5 text-xs text-muted-foreground"
                                >
                                    {tag}
                                </span>
                            ))}
                        </div>
                    )}

                    <h1 className="mt-2 text-3xl leading-tight font-semibold">
                        {post.title}
                    </h1>

                    {post.cover_url && (
                        <img
                            src={post.cover_url}
                            alt={post.title ?? ''}
                            className="mt-6 w-full rounded-lg object-cover"
                        />
                    )}

                    {post.excerpt && (
                        <p className="mt-6 text-lg text-muted-foreground">
                            {post.excerpt}
                        </p>
                    )}

                    {post.body && (
                        // Body is sanitised server-side (App\Support\HtmlSanitizer) before storage.
                        <div
                            className="rte-content mt-6 leading-relaxed"
                            dangerouslySetInnerHTML={{ __html: post.body }}
                        />
                    )}

                    {post.gallery && post.gallery.length > 0 && (
                        <div className="mt-8">
                            <h3 className="mb-4 text-lg font-semibold">{t('common.photo_gallery') || 'Фотогалерея'}</h3>
                            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                                {post.gallery.map((img, i) => (
                                    <a key={i} href={img.url} target="_blank" rel="noreferrer" className="block aspect-video overflow-hidden rounded-lg bg-muted border hover:border-primary transition-colors">
                                        <img src={img.thumb || img.url} alt="" className="h-full w-full object-cover transition-transform hover:scale-105" />
                                    </a>
                                ))}
                            </div>
                        </div>
                    )}

                    {post.attachments && post.attachments.length > 0 && (
                        <div className="mt-8">
                            <h3 className="mb-4 flex items-center gap-2 text-lg font-semibold">
                                <Paperclip className="size-5" />
                                {t('common.attachments') || 'Прикрепленные файлы'}
                            </h3>
                            <ul className="flex flex-col gap-3">
                                {post.attachments.map((file, i) => (
                                    <li key={i}>
                                        <a href={file.url} download className="group flex items-center gap-3 rounded-lg border bg-card p-3 shadow-sm hover:border-primary hover:shadow-md transition-all">
                                            <div className="flex size-10 items-center justify-center rounded-md bg-primary/10 text-primary">
                                                <FileIcon className="size-5" />
                                            </div>
                                            <div className="flex flex-col">
                                                <span className="text-sm font-medium text-foreground group-hover:text-primary transition-colors line-clamp-1">{file.name}</span>
                                                <span className="text-xs text-muted-foreground uppercase">{file.ext} · {file.size}</span>
                                            </div>
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {post.author && (
                        <p className="mt-8 text-sm text-muted-foreground">
                            {t('news.author', { author: post.author })}
                        </p>
                    )}
                </article>

                <aside className="space-y-4">
                    <h2 className="text-lg font-semibold">
                        {t('common.related_news') || 'Похожие материалы'}
                    </h2>
                    <ul className="space-y-3">
                        {related.map((item) => (
                            <li key={item.slug}>
                                <Link
                                    href={
                                        show({ locale, slug: item.slug ?? '' })
                                            .url
                                    }
                                    className="block text-sm hover:text-primary"
                                >
                                    {item.title}
                                    {item.published_at && (
                                        <span className="block text-xs text-muted-foreground mt-1">
                                            {formatDate(item.published_at, locale)}
                                        </span>
                                    )}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </aside>
            </div>
        </>
    );
}
