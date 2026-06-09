import { Head, Link, usePage } from '@inertiajs/react';
import { index as newsIndex, show } from '@/routes/news';

type Article = {
    title: string;
    excerpt: string | null;
    body: string | null;
    type_label: string;
    category: string | null;
    cover_url: string | null;
    author: string | null;
    published_at: string | null;
};

type RecentItem = {
    title: string | null;
    slug: string | null;
    published_at: string | null;
};

type PageProps = {
    post: Article;
    recent: RecentItem[];
};

export default function NewsShow({ post, recent }: PageProps) {
    const { locale } = usePage().props;

    return (
        <>
            <Head title={post.title} />

            <div className="grid gap-10 lg:grid-cols-[1fr_320px]">
                <article className="min-w-0">
                    <Link href={newsIndex({ locale }).url} className="text-sm text-primary hover:underline">
                        ← К списку новостей
                    </Link>

                    <div className="mt-3 flex items-center gap-2 text-sm text-muted-foreground">
                        <span className="text-primary">{post.category ?? post.type_label}</span>
                        {post.published_at && <span>· {post.published_at}</span>}
                    </div>

                    <h1 className="mt-2 text-3xl font-semibold leading-tight">{post.title}</h1>

                    {post.cover_url && (
                        <img src={post.cover_url} alt="" className="mt-6 w-full rounded-lg object-cover" />
                    )}

                    {post.excerpt && <p className="mt-6 text-lg text-muted-foreground">{post.excerpt}</p>}

                    {post.body && (
                        // Body is sanitised server-side (App\Support\HtmlSanitizer) before storage.
                        <div
                            className="rte-content mt-6 leading-relaxed"
                            dangerouslySetInnerHTML={{ __html: post.body }}
                        />
                    )}

                    {post.author && (
                        <p className="mt-8 text-sm text-muted-foreground">Автор: {post.author}</p>
                    )}
                </article>

                <aside className="space-y-4">
                    <h2 className="text-lg font-semibold">Последние новости</h2>
                    <ul className="space-y-3">
                        {recent.map((item) => (
                            <li key={item.slug}>
                                <Link
                                    href={show({ locale, slug: item.slug ?? '' }).url}
                                    className="block text-sm hover:text-primary"
                                >
                                    {item.title}
                                    {item.published_at && (
                                        <span className="block text-xs text-muted-foreground">{item.published_at}</span>
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
