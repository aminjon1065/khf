import { Head, Link, usePage } from '@inertiajs/react';
import { Bell, Map, Phone, ShieldAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { AppEmblem } from '@/components/app-emblem';
import { useTranslations } from '@/hooks/use-translations';
import { index as newsIndex, show } from '@/routes/news';
import { EmergencyHero, ActiveAlert } from '@/components/Public/EmergencyHero';
import { HomeSlider } from '@/components/home-slider';

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

export default function Home({ latestPosts }: PageProps) {
    const { locale, activeAlerts } = usePage().props as { locale: string; activeAlerts?: ActiveAlert[] };
    const { t } = useTranslations();

    const criticalAlerts = (activeAlerts ?? []).filter(a => a.level === 'critical');
    const isRedState = criticalAlerts.length > 0;

    const quickLinks = [
        {
            icon: Phone,
            label: t('home.quick_links.emergency_phone'),
            value: '112',
        },
        {
            icon: ShieldAlert,
            label: t('home.quick_links.safety_guides_label'),
            value: t('home.quick_links.safety_guides_hint'),
        },
        {
            icon: Map,
            label: t('common.emergency_map'),
            value: t('common.operational_situation'),
        },
        {
            icon: Bell,
            label: t('home.quick_links.subscribe_label'),
            value: t('home.quick_links.subscribe_hint'),
        },
    ];

    return (
        <>
            <Head title={t('home.meta_title')} />

            {isRedState ? (
                <EmergencyHero alerts={criticalAlerts} />
            ) : (
                <HomeSlider latestPosts={latestPosts} locale={locale} t={t} />
            )}

            <section className="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                {quickLinks.map((item) => (
                    <div
                        key={item.label}
                        className="group flex flex-col items-center gap-4 rounded-2xl border bg-card p-6 text-center shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md"
                    >
                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground">
                            <item.icon className="size-6" />
                        </div>
                        <div>
                            <p className="font-semibold text-foreground">{item.label}</p>
                            <p className="mt-1 text-sm text-muted-foreground leading-relaxed">
                                {item.value}
                            </p>
                        </div>
                    </div>
                ))}
            </section>

            <section className="mt-20">
                <div className="mb-8 flex items-end justify-between border-b pb-4">
                    <h2 className="text-3xl font-bold tracking-tight text-foreground">
                        {t('common.latest_news')}
                    </h2>
                    <Link
                        href={newsIndex({ locale }).url}
                        className="text-sm font-medium text-primary transition-colors hover:text-primary/80 hover:underline"
                    >
                        {t('home.news.view_all')}
                    </Link>
                </div>

                {latestPosts.length === 0 ? (
                    <div className="flex h-40 items-center justify-center rounded-xl border border-dashed text-muted-foreground">
                        <p>{t('common.no_publications')}</p>
                    </div>
                ) : (
                    <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        {latestPosts.map((post) => (
                            <Link
                                key={post.slug}
                                href={
                                    show({ locale, slug: post.slug ?? '' }).url
                                }
                                className="group flex flex-col overflow-hidden rounded-2xl border bg-card shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                            >
                                <div className="relative aspect-[16/10] w-full overflow-hidden bg-muted">
                                    {post.cover_url ? (
                                        <img
                                            src={post.cover_url}
                                            alt=""
                                            className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                        />
                                    ) : (
                                        <div className="absolute inset-0 flex items-center justify-center bg-secondary">
                                            <AppEmblem className="size-12 text-muted-foreground/30" />
                                        </div>
                                    )}
                                </div>
                                <div className="flex flex-1 flex-col p-6">
                                    <div className="mb-3 flex items-center gap-3 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                        {post.category && (
                                            <span className="text-primary">
                                                {post.category}
                                            </span>
                                        )}
                                        {post.published_at && (
                                            <span>{post.published_at}</span>
                                        )}
                                    </div>
                                    <h3 className="text-xl font-bold leading-tight text-foreground transition-colors group-hover:text-primary">
                                        {post.title}
                                    </h3>
                                    {post.excerpt && (
                                        <p className="mt-3 line-clamp-2 text-sm leading-relaxed text-muted-foreground">
                                            {post.excerpt}
                                        </p>
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
