import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowUpRight, Inbox, Map, ShieldAlert } from 'lucide-react';
import { BlockRenderer } from '@/components/Public/block-renderer';
import type { ActiveAlert } from '@/components/Public/EmergencyHero';
import { EmergencyHero } from '@/components/Public/EmergencyHero';
import { EmptyState } from '@/components/Public/empty-state';
import { MapWidget } from '@/components/Public/map-widget';
import { NewsCarousel } from '@/components/Public/news-carousel';
import { NewsCover } from '@/components/Public/news-cover';
import { OperationalStrip } from '@/components/Public/operational-strip';
import { PresidentCard } from '@/components/Public/president-card';
import { SubscriptionWidget } from '@/components/Public/subscription-widget';
import { useTranslations } from '@/hooks/use-translations';
import { create as appealsCreate } from '@/routes/appeals';
import { index as guidesIndex } from '@/routes/guides';
import { index as mapIndex } from '@/routes/map';
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
    operational?: {
        active: number;
        controlled: number;
        resolved: number;
    };
    mapIncidents?: any[];
    blocks?: any[];
};

export default function Home({
    latestPosts,
    operational,
    mapIncidents = [],
    blocks,
}: PageProps) {
    const { locale, activeAlerts } = usePage().props as {
        locale: string;
        activeAlerts?: ActiveAlert[];
    };
    const { t } = useTranslations();

    const criticalAlerts = (activeAlerts ?? []).filter(
        (a) => a.level === 'critical',
    );
    const isRedState = criticalAlerts.length > 0;

    // Featured posts lead the carousel; the remainder fill the grid below (ТЗ §6.1).
    const featuredPosts = latestPosts.slice(0, 3);
    const newsGridPosts = isRedState ? latestPosts : latestPosts.slice(3);

    // Task-first service grid (govtech): every tile is an actionable destination, not decoration.
    const tasks = [
        {
            icon: Map,
            label: t('common.emergency_map'),
            hint: t('common.operational_situation'),
            href: mapIndex({ locale }).url,
            accent: false,
        },
        {
            icon: ShieldAlert,
            label: t('home.quick_links.safety_guides_label'),
            hint: t('home.quick_links.safety_guides_hint'),
            href: guidesIndex({ locale }).url,
            accent: false,
        },
        {
            icon: Inbox,
            label: t('nav.reception'),
            hint: t('appeals.subtitle'),
            href: appealsCreate({ locale }).url,
            accent: false,
        },
    ];

    return (
        <>
            <Head title={t('home.meta_title')} />

            {isRedState ? (
                <EmergencyHero alerts={criticalAlerts} />
            ) : (
                <>
                    <div className="flex flex-col gap-4 lg:grid lg:grid-cols-3 lg:items-stretch">
                        <div className="min-w-0 lg:col-span-2">
                            <NewsCarousel
                                posts={featuredPosts}
                                locale={locale}
                            />
                        </div>
                        <PresidentCard />
                    </div>
                    <OperationalStrip operational={operational} />
                </>
            )}

            {blocks && blocks.length > 0 ? (
                <section className="mt-12">
                    <BlockRenderer
                        blocks={blocks}
                        latestPosts={latestPosts
                            .filter(
                                (
                                    p,
                                ): p is NewsCard & {
                                    slug: string;
                                    title: string;
                                } => Boolean(p.slug && p.title),
                            )
                            .map((p) => ({
                                slug: p.slug,
                                title: p.title,
                                cover_url: p.cover_url,
                            }))}
                    />
                </section>
            ) : (
                <>
                    <section className="mt-12 grid gap-6 lg:grid-cols-4">
                        <div className="lg:col-span-3">
                            <MapWidget
                                locale={locale}
                                incidents={mapIncidents}
                            />
                        </div>
                        <div className="flex flex-col gap-6">
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                                {tasks.map((item) => (
                                    <Link
                                        key={item.label}
                                        href={item.href}
                                        className="group flex items-start gap-4 rounded-2xl border bg-card p-5 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                    >
                                        <span
                                            className={`flex size-11 shrink-0 items-center justify-center rounded-xl transition-colors ${
                                                item.accent
                                                    ? 'bg-signal/10 text-signal group-hover:bg-signal group-hover:text-signal-foreground'
                                                    : 'bg-primary/10 text-primary group-hover:bg-primary group-hover:text-primary-foreground'
                                            }`}
                                        >
                                            <item.icon className="size-5.5" />
                                        </span>
                                        <span className="flex-1">
                                            <span className="flex items-center gap-1 font-semibold text-foreground">
                                                {item.label}
                                                <ArrowUpRight className="size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-primary" />
                                            </span>
                                            <span className="mt-1 line-clamp-2 block text-sm leading-relaxed text-muted-foreground">
                                                {item.hint}
                                            </span>
                                        </span>
                                    </Link>
                                ))}
                            </div>
                            <SubscriptionWidget locale={locale} />
                        </div>
                    </section>

                    <section className="mt-10 lg:mt-12">
                        <div className="mb-8 flex items-end justify-between border-b border-border pb-4">
                            <h2 className="flex items-center gap-3 text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                                <span
                                    className="h-7 w-1.5 rounded-full bg-signal"
                                    aria-hidden="true"
                                />
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
                            <EmptyState message={t('common.no_publications')} />
                        ) : newsGridPosts.length > 0 ? (
                            <div className="grid gap-6 sm:grid-cols-2 sm:gap-8 lg:grid-cols-3">
                                {newsGridPosts.map((post) => (
                                    <Link
                                        key={post.slug}
                                        href={
                                            show({
                                                locale,
                                                slug: post.slug ?? '',
                                            }).url
                                        }
                                        className="group flex min-w-0 flex-col overflow-hidden rounded-2xl border bg-card shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                                    >
                                        <NewsCover
                                            src={post.cover_url}
                                            locale={locale}
                                            loading="lazy"
                                            imgClassName="transition-transform duration-500 group-hover:scale-105"
                                        />
                                        <div className="flex flex-1 flex-col p-6">
                                            <div className="mb-3 flex items-center gap-3 text-xs font-medium tracking-wider text-muted-foreground uppercase">
                                                {post.category && (
                                                    <span className="text-primary">
                                                        {post.category}
                                                    </span>
                                                )}
                                                {post.published_at && (
                                                    <span>
                                                        {post.published_at}
                                                    </span>
                                                )}
                                            </div>
                                            <h3 className="text-xl leading-tight font-bold text-foreground transition-colors group-hover:text-primary">
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
                        ) : null}
                    </section>
                </>
            )}
        </>
    );
}
