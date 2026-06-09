import { Head, Link, usePage } from '@inertiajs/react';
import { Bell, Map, Phone, ShieldAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
    latestPosts: NewsCard[];
};

const quickLinks = [
    { icon: Phone, label: 'Экстренный телефон', value: '112' },
    { icon: ShieldAlert, label: 'Памятки по безопасности', value: 'Как действовать при ЧС' },
    { icon: Map, label: 'Карта ЧС', value: 'Оперативная обстановка' },
    { icon: Bell, label: 'Подписка', value: 'Уведомления об угрозах' },
];

export default function Home({ latestPosts }: PageProps) {
    const { locale } = usePage().props;

    return (
        <>
            <Head title="Главная" />

            <section className="rounded-xl bg-primary px-6 py-12 text-primary-foreground sm:px-10">
                <h1 className="max-w-3xl text-3xl font-semibold sm:text-4xl">
                    Комитет по чрезвычайным ситуациям и гражданской обороне
                </h1>
                <p className="mt-3 max-w-2xl text-primary-foreground/80">
                    Оперативная информация об угрозах и чрезвычайных ситуациях, памятки по безопасности
                    и оповещения населения Республики Таджикистан.
                </p>
                <div className="mt-6 flex flex-wrap gap-3">
                    <Button variant="signal" asChild>
                        <Link href={newsIndex({ locale }).url}>Последние новости</Link>
                    </Button>
                    <a
                        href="tel:112"
                        className="inline-flex items-center gap-2 rounded-md border border-primary-foreground/30 px-4 py-2 text-sm font-medium"
                    >
                        <Phone className="size-4" />
                        Экстренный вызов: 112
                    </a>
                </div>
            </section>

            <section className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {quickLinks.map((item) => (
                    <div key={item.label} className="flex items-start gap-3 rounded-lg border p-4">
                        <item.icon className="size-6 shrink-0 text-primary" />
                        <div>
                            <p className="font-medium">{item.label}</p>
                            <p className="text-sm text-muted-foreground">{item.value}</p>
                        </div>
                    </div>
                ))}
            </section>

            <section className="mt-12">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">Последние новости</h2>
                    <Link href={newsIndex({ locale }).url} className="text-sm text-primary hover:underline">
                        Все новости →
                    </Link>
                </div>

                {latestPosts.length === 0 ? (
                    <p className="text-muted-foreground">Публикаций пока нет.</p>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {latestPosts.map((post) => (
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
                                        {post.category && <span className="text-primary">{post.category}</span>}
                                        {post.published_at && <span>{post.published_at}</span>}
                                    </div>
                                    <h3 className="font-semibold leading-snug group-hover:text-primary">{post.title}</h3>
                                    {post.excerpt && (
                                        <p className="line-clamp-2 text-sm text-muted-foreground">{post.excerpt}</p>
                                    )}
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </section>
        </>
    );
}
