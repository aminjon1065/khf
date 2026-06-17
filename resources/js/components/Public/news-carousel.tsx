import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Pause, Play } from 'lucide-react';
import { useEffect, useState } from 'react';
import { AppEmblem } from '@/components/app-emblem';
import { useTranslations } from '@/hooks/use-translations';
import { show as newsShow } from '@/routes/news';

type NewsCard = {
    title: string | null;
    slug: string | null;
    excerpt: string | null;
    category: string | null;
    cover_url: string | null;
    published_at: string | null;
};

const AUTOPLAY_MS = 6000;

/**
 * Accessible featured-news carousel (ТЗ §6.1). Auto-advance is WCAG 2.2.2 compliant: it pauses on
 * hover/focus and when the tab is hidden, exposes an explicit pause/play control, is silenced for
 * assistive tech while rotating (`aria-live="off"`), and never auto-rotates under
 * `prefers-reduced-motion`. Slides off-screen are made `inert` so they stay out of the tab order.
 */
export function NewsCarousel({
    posts,
    locale,
}: {
    posts: NewsCard[];
    locale: string;
}) {
    const { t } = useTranslations();
    const total = posts.length;

    const [current, setCurrent] = useState(0);
    const [isPlaying, setIsPlaying] = useState(true);
    const [hovered, setHovered] = useState(false);
    const [focused, setFocused] = useState(false);
    const [hidden, setHidden] = useState(false);
    const [reducedMotion, setReducedMotion] = useState(false);

    useEffect(() => {
        const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
        const apply = () => {
            setReducedMotion(mq.matches);

            if (mq.matches) {
                setIsPlaying(false);
            }
        };

        apply();
        mq.addEventListener('change', apply);

        return () => mq.removeEventListener('change', apply);
    }, []);

    useEffect(() => {
        const onVisibility = () => setHidden(document.hidden);
        document.addEventListener('visibilitychange', onVisibility);

        return () =>
            document.removeEventListener('visibilitychange', onVisibility);
    }, []);

    const autopaused = hovered || focused || hidden;

    useEffect(() => {
        if (!isPlaying || autopaused || reducedMotion || total <= 1) {
            return;
        }

        const id = setInterval(
            () => setCurrent((c) => (c + 1) % total),
            AUTOPLAY_MS,
        );

        return () => clearInterval(id);
    }, [isPlaying, autopaused, reducedMotion, total, current]);

    if (total === 0) {
        return null;
    }

    const goTo = (index: number) =>
        setCurrent(((index % total) + total) % total);

    const onKeyDown = (event: React.KeyboardEvent) => {
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            goTo(current - 1);
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            goTo(current + 1);
        }
    };

    const liveValue: 'off' | 'polite' =
        isPlaying && !autopaused && !reducedMotion ? 'off' : 'polite';

    return (
        <section
            aria-roledescription="carousel"
            aria-label={t('home.slider.carousel_label')}
            onMouseEnter={() => setHovered(true)}
            onMouseLeave={() => setHovered(false)}
            onFocusCapture={() => setFocused(true)}
            onBlurCapture={() => setFocused(false)}
            onKeyDown={onKeyDown}
            className="relative overflow-hidden rounded-2xl border border-border bg-card shadow-sm"
        >
            <div
                aria-live={liveValue}
                className="relative h-[300px] sm:h-[400px] lg:h-[460px]"
            >
                <div
                    className={`flex h-full w-full ${
                        reducedMotion
                            ? ''
                            : 'transition-transform duration-700 ease-in-out'
                    }`}
                    style={{ transform: `translateX(-${current * 100}%)` }}
                >
                    {posts.map((post, index) => (
                        <article
                            key={post.slug ?? index}
                            role="group"
                            aria-roledescription={t('home.slider.slide')}
                            aria-label={`${index + 1} / ${total}`}
                            aria-hidden={index !== current}
                            inert={index !== current}
                            className="relative h-full w-full shrink-0"
                        >
                            {post.cover_url ? (
                                <img
                                    src={post.cover_url}
                                    alt=""
                                    loading={index === 0 ? 'eager' : 'lazy'}
                                    className="absolute inset-0 h-full w-full object-cover"
                                />
                            ) : (
                                <div className="absolute inset-0 flex items-center justify-center bg-brand">
                                    <AppEmblem className="size-24 text-white/10" />
                                </div>
                            )}

                            <div
                                className="absolute inset-0 bg-gradient-to-t from-black/85 via-black/45 to-transparent"
                                aria-hidden="true"
                            />

                            <div className="absolute inset-x-0 bottom-0 p-6 sm:p-8 lg:p-10">
                                <div className="flex flex-wrap items-center gap-3 text-xs font-medium tracking-wider text-white/90 uppercase">
                                    {post.category && (
                                        <span className="rounded-full bg-white/15 px-2.5 py-1 ring-1 ring-white/25 backdrop-blur-sm">
                                            {post.category}
                                        </span>
                                    )}
                                    {post.published_at && (
                                        <span>{post.published_at}</span>
                                    )}
                                </div>

                                <h3 className="mt-3 max-w-3xl text-xl leading-tight font-bold text-white sm:text-2xl lg:text-3xl">
                                    {post.slug ? (
                                        <Link
                                            href={
                                                newsShow({
                                                    locale,
                                                    slug: post.slug,
                                                }).url
                                            }
                                            className="line-clamp-2 transition-colors hover:text-white/80 focus-visible:underline focus-visible:outline-none"
                                        >
                                            {post.title}
                                        </Link>
                                    ) : (
                                        <span className="line-clamp-2">
                                            {post.title}
                                        </span>
                                    )}
                                </h3>

                                {post.excerpt && (
                                    <p className="mt-2 line-clamp-2 hidden max-w-2xl text-sm leading-relaxed text-white/80 sm:block">
                                        {post.excerpt}
                                    </p>
                                )}

                                {post.slug && (
                                    <Link
                                        href={
                                            newsShow({
                                                locale,
                                                slug: post.slug,
                                            }).url
                                        }
                                        className="mt-4 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-brand shadow-sm transition-colors hover:bg-white/90 focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-black/40 focus-visible:outline-none"
                                    >
                                        {t('home.slider.news_read_more')}
                                        <ChevronRight
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                    </Link>
                                )}
                            </div>
                        </article>
                    ))}
                </div>
            </div>

            {total > 1 && (
                <>
                    <button
                        type="button"
                        onClick={() => goTo(current - 1)}
                        aria-label={t('home.slider.prev')}
                        className="absolute top-1/2 left-3 z-10 flex size-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/20 bg-black/30 text-white backdrop-blur-sm transition-colors hover:bg-black/50 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                    >
                        <ChevronLeft className="size-5" aria-hidden="true" />
                    </button>
                    <button
                        type="button"
                        onClick={() => goTo(current + 1)}
                        aria-label={t('home.slider.next')}
                        className="absolute top-1/2 right-3 z-10 flex size-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/20 bg-black/30 text-white backdrop-blur-sm transition-colors hover:bg-black/50 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                    >
                        <ChevronRight className="size-5" aria-hidden="true" />
                    </button>

                    <div className="absolute top-4 right-4 z-10 flex items-center gap-3 rounded-full border border-white/15 bg-black/30 px-3 py-1.5 backdrop-blur-sm">
                        <div className="flex items-center gap-2">
                            {posts.map((_, index) => (
                                <button
                                    key={index}
                                    type="button"
                                    onClick={() => goTo(index)}
                                    aria-label={t('home.slider.go_to', {
                                        number: index + 1,
                                    })}
                                    aria-current={index === current}
                                    className={`h-2.5 cursor-pointer rounded-full transition-all focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none ${
                                        index === current
                                            ? 'w-6 bg-white'
                                            : 'w-2.5 bg-white/45 hover:bg-white/75'
                                    }`}
                                />
                            ))}
                        </div>

                        {!reducedMotion && (
                            <button
                                type="button"
                                onClick={() => setIsPlaying((p) => !p)}
                                aria-label={
                                    isPlaying
                                        ? t('home.slider.pause')
                                        : t('home.slider.play')
                                }
                                aria-pressed={!isPlaying}
                                className="flex size-6 cursor-pointer items-center justify-center rounded-full text-white/90 transition-colors hover:text-white focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                            >
                                {isPlaying ? (
                                    <Pause
                                        className="size-3.5"
                                        aria-hidden="true"
                                    />
                                ) : (
                                    <Play
                                        className="size-3.5"
                                        aria-hidden="true"
                                    />
                                )}
                            </button>
                        )}
                    </div>
                </>
            )}
        </section>
    );
}
