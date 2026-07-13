import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Pause, Play } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { AppEmblem } from '@/components/app-emblem';
import { useTranslations } from '@/hooks/use-translations';
import { index as newsIndex, show as newsShow } from '@/routes/news';

export type NewsCarouselPost = {
    title: string | null;
    slug: string | null;
    excerpt: string | null;
    category: string | null;
    cover_url: string | null;
    published_at: string | null;
    /** When set, "Read more" links here instead of a news article. */
    href?: string | null;
};

const AUTOPLAY_MS = 6000;

const SLIDE_HEIGHT =
    'min-h-[340px] h-[340px] sm:min-h-[400px] sm:h-[400px] lg:min-h-[460px] lg:h-[460px]';

/**
 * Featured-news carousel for the homepage hero (ТЗ §6.1). Solid brand panel with optional cover
 * wash, emblem in the meta row, and WCAG-compliant auto-advance (pause on hover/focus/hidden tab).
 */
export function NewsCarousel({
    posts,
    locale,
}: {
    posts: NewsCarouselPost[];
    locale: string;
}) {
    const { t } = useTranslations();

    const slides = useMemo((): NewsCarouselPost[] => {
        if (posts.length > 0) {
            return posts;
        }

        return [
            {
                title: t('home.slider.news_fallback_title'),
                slug: null,
                excerpt: t('home.slider.news_fallback_text'),
                category: t('home.slider.news_badge'),
                cover_url: null,
                published_at: null,
                href: newsIndex({ locale }).url,
            },
        ];
    }, [posts, locale, t]);

    const total = slides.length;

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

    const articleHref = (post: NewsCarouselPost): string | null => {
        if (post.href) {
            return post.href;
        }

        if (post.slug) {
            return newsShow({ locale, slug: post.slug }).url;
        }

        return null;
    };

    return (
        <section
            aria-roledescription="carousel"
            aria-label={t('home.slider.carousel_label')}
            onMouseEnter={() => setHovered(true)}
            onMouseLeave={() => setHovered(false)}
            onFocusCapture={() => setFocused(true)}
            onBlurCapture={() => setFocused(false)}
            onKeyDown={onKeyDown}
            className={`relative isolate overflow-hidden rounded-2xl border border-border bg-brand shadow-sm ${SLIDE_HEIGHT}`}
        >
            <div
                aria-live={liveValue}
                className="relative h-full overflow-hidden"
            >
                <div
                    className={`flex h-full w-full will-change-transform ${
                        reducedMotion
                            ? ''
                            : 'transition-transform duration-700 ease-in-out'
                    }`}
                    style={{ transform: `translateX(-${current * 100}%)` }}
                >
                    {slides.map((post, index) => {
                        const href = articleHref(post);

                        return (
                            <article
                                key={post.slug ?? post.href ?? index}
                                role="group"
                                aria-roledescription={t('home.slider.slide')}
                                aria-label={`${index + 1} / ${total}`}
                                aria-hidden={index !== current}
                                inert={index !== current}
                                className="relative h-full w-full shrink-0 bg-brand"
                            >
                                {post.cover_url && (
                                    <img
                                        src={post.cover_url}
                                        alt=""
                                        loading={index === 0 ? 'eager' : 'lazy'}
                                        className="absolute inset-0 h-full w-full object-cover opacity-20"
                                    />
                                )}

                                <div
                                    className="absolute inset-0 bg-gradient-to-br from-brand via-brand/95 to-brand-strong/90"
                                    aria-hidden="true"
                                />

                                <div className="relative flex h-full flex-col justify-center px-5 py-6 pr-12 pl-12 sm:px-10 sm:py-10 sm:pr-14 sm:pl-14 lg:px-12 lg:pr-16 lg:pl-16">
                                    <div className="flex flex-wrap items-center gap-x-3 gap-y-2 pe-16 text-xs font-medium tracking-wider text-white/90 uppercase sm:pe-0">
                                        {post.category && (
                                            <span className="rounded-full bg-black/30 px-2.5 py-1 ring-1 ring-white/15">
                                                {post.category}
                                            </span>
                                        )}
                                        {post.published_at && (
                                            <span className="tracking-normal text-white/85 normal-case">
                                                {post.published_at}
                                            </span>
                                        )}
                                        <AppEmblem
                                            locale={locale}
                                            className="ms-auto hidden size-9 sm:block sm:size-10"
                                            alt=""
                                        />
                                    </div>

                                    <h3 className="mt-4 max-w-2xl text-xl leading-tight font-bold text-white sm:mt-5 sm:text-2xl lg:text-3xl">
                                        {href ? (
                                            <Link
                                                href={href}
                                                className="line-clamp-3 transition-colors hover:text-white/85 focus-visible:underline focus-visible:outline-none"
                                            >
                                                {post.title}
                                            </Link>
                                        ) : (
                                            <span className="line-clamp-3">
                                                {post.title}
                                            </span>
                                        )}
                                    </h3>

                                    {post.excerpt && (
                                        <p className="mt-2 line-clamp-2 max-w-xl text-sm leading-relaxed text-white/80 sm:mt-3 sm:line-clamp-3 sm:text-base">
                                            {post.excerpt}
                                        </p>
                                    )}

                                    {href && (
                                        <Link
                                            href={href}
                                            className="mt-4 inline-flex w-fit items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-brand shadow-sm transition-colors hover:bg-white/90 focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand focus-visible:outline-none sm:mt-6"
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
                        );
                    })}
                </div>
            </div>

            {total > 1 && (
                <>
                    <button
                        type="button"
                        onClick={() => goTo(current - 1)}
                        aria-label={t('home.slider.prev')}
                        className="absolute top-1/2 left-2 z-10 flex size-9 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/20 bg-black/30 text-white backdrop-blur-sm transition-colors hover:bg-black/50 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none sm:left-3 sm:size-10"
                    >
                        <ChevronLeft className="size-5" aria-hidden="true" />
                    </button>
                    <button
                        type="button"
                        onClick={() => goTo(current + 1)}
                        aria-label={t('home.slider.next')}
                        className="absolute top-1/2 right-2 z-10 flex size-9 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/20 bg-black/30 text-white backdrop-blur-sm transition-colors hover:bg-black/50 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none sm:right-3 sm:size-10"
                    >
                        <ChevronRight className="size-5" aria-hidden="true" />
                    </button>

                    <div className="absolute right-3 bottom-3 left-3 z-10 flex items-center justify-center gap-3 sm:top-4 sm:right-4 sm:bottom-auto sm:left-auto sm:justify-start">
                        <div className="flex items-center gap-2 rounded-full border border-white/15 bg-black/30 px-3 py-1.5 backdrop-blur-sm">
                            {slides.map((slide, index) => (
                                <button
                                    key={slide.slug ?? slide.href ?? index}
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
                    </div>
                </>
            )}
        </section>
    );
}
