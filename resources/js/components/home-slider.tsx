import { Link } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Phone,
    ShieldAlert,
    Users,
} from 'lucide-react';
import { useEffect, useState, useRef } from 'react';
import { AppEmblem } from '@/components/app-emblem';
import { Button } from '@/components/ui/button';
import { index as guidesIndex } from '@/routes/guides';
import { index as newsIndex, show as newsShow } from '@/routes/news';
import { create as touristGroupsCreate } from '@/routes/tourist-groups';

type NewsCard = {
    title: string | null;
    slug: string | null;
    excerpt: string | null;
    category: string | null;
    cover_url: string | null;
    published_at: string | null;
};

type HomeSliderProps = {
    latestPosts: NewsCard[];
    locale: string;
    t: (key: string, params?: Record<string, any>) => string;
};

export function HomeSlider({ latestPosts, locale, t }: HomeSliderProps) {
    const [current, setCurrent] = useState(0);
    const [isHovered, setIsHovered] = useState(false);
    const timerRef = useRef<NodeJS.Timeout | null>(null);

    const slidesCount = 3;

    const nextSlide = () => {
        setCurrent((prev) => (prev + 1) % slidesCount);
    };

    const prevSlide = () => {
        setCurrent((prev) => (prev - 1 + slidesCount) % slidesCount);
    };

    // Auto-play effect
    useEffect(() => {
        if (isHovered) {
            if (timerRef.current) {
                clearInterval(timerRef.current);
            }

            return;
        }

        timerRef.current = setInterval(nextSlide, 6000);

        return () => {
            if (timerRef.current) {
                clearInterval(timerRef.current);
            }
        };
    }, [isHovered]);

    const latestPost =
        latestPosts && latestPosts.length > 0 ? latestPosts[0] : null;

    return (
        <section
            onMouseEnter={() => setIsHovered(true)}
            onMouseLeave={() => setIsHovered(false)}
            className="xs:h-[400px] group relative h-[380px] overflow-hidden rounded-2xl border border-border/50 bg-[#0f172a] text-white shadow-xl transition-all duration-700 sm:h-[440px] md:h-[460px]"
        >
            {/* Slide Wrapper */}
            <div className="relative h-full w-full">
                {/* SLIDE 1: EMERGENCY CALL HOTLINE */}
                <div
                    className={`absolute inset-0 flex h-full w-full items-center transition-all duration-700 ease-in-out ${
                        current === 0
                            ? 'z-10 translate-x-0 opacity-100'
                            : 'pointer-events-none z-0 translate-x-full opacity-0'
                    }`}
                >
                    <div className="absolute inset-0 z-1 bg-gradient-to-r from-red-950/90 via-slate-950/80 to-slate-900/60" />
                    <div className="absolute inset-0 z-1 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-red-600/20 via-transparent to-transparent" />

                    <div className="xs:px-8 relative z-10 flex max-w-xl flex-col items-start gap-2 px-5 py-6 text-left sm:max-w-3xl sm:gap-4 sm:px-16 sm:py-0">
                        <div className="flex items-center gap-2 rounded-full border border-red-500/30 bg-red-600/10 px-3 py-0.5 text-[10px] font-semibold tracking-wider text-red-400 uppercase sm:text-xs">
                            <ShieldAlert className="size-3.5 animate-pulse" />
                            {t('home.hero.emergency_call')}
                        </div>
                        <h2 className="xs:text-2xl text-xl leading-tight font-extrabold tracking-tight sm:text-4xl md:text-5xl">
                            {t('home.slider.rescue_title')}
                        </h2>
                        <p className="xs:line-clamp-none line-clamp-3 max-w-xl text-xs leading-relaxed text-slate-300 sm:text-sm md:text-base">
                            {t('home.slider.rescue_text')}
                        </p>
                        <div className="xs:flex-row xs:w-auto mt-2 flex w-full flex-col gap-2 sm:mt-4 sm:gap-4">
                            <a
                                href="tel:112"
                                className="xs:w-auto inline-flex w-full items-center justify-center gap-2 rounded-full bg-red-600 px-5 py-2 text-xs font-bold text-white shadow-md transition-all hover:scale-105 hover:bg-red-700 sm:px-8 sm:py-3 sm:text-sm"
                            >
                                <Phone className="size-3.5 fill-white sm:size-4" />
                                {t('home.slider.rescue_call')}
                            </a>
                            <Button
                                variant="outline"
                                className="xs:w-auto w-full rounded-full border-slate-700 bg-slate-900/40 px-5 py-2 text-xs text-white hover:bg-slate-800 sm:px-8 sm:py-3 sm:text-sm"
                                asChild
                            >
                                <Link href={guidesIndex({ locale }).url}>
                                    {t('home.slider.rescue_instructions')}
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                {/* SLIDE 2: LATEST PUBLICATION */}
                <div
                    className={`absolute inset-0 flex h-full w-full items-center transition-all duration-700 ease-in-out ${
                        current === 1
                            ? 'z-10 translate-x-0 opacity-100'
                            : 'pointer-events-none z-0 translate-x-full opacity-0'
                    }`}
                >
                    {latestPost && latestPost.cover_url ? (
                        <div
                            className="absolute inset-0 scale-105 transform bg-cover bg-center transition-transform duration-10000 ease-linear"
                            style={{
                                backgroundImage: `url(${latestPost.cover_url})`,
                            }}
                        />
                    ) : (
                        <div className="absolute inset-0 bg-gradient-to-r from-blue-950 via-slate-900 to-slate-800" />
                    )}
                    <div className="absolute inset-0 z-1 bg-gradient-to-r from-slate-950/95 via-slate-950/80 to-slate-950/30" />

                    <div className="xs:px-8 relative z-10 flex max-w-xl flex-col items-start gap-2 px-5 py-6 text-left sm:max-w-3xl sm:gap-4 sm:px-16 sm:py-0">
                        <div className="flex items-center gap-2 rounded-full border border-blue-400/30 bg-blue-500/10 px-3 py-0.5 text-[10px] font-semibold tracking-wider text-blue-400 uppercase sm:text-xs">
                            <AppEmblem className="size-3.5" />
                            {t('home.slider.news_badge')}
                        </div>
                        <h2 className="xs:text-xl line-clamp-2 text-lg leading-tight font-extrabold tracking-tight sm:text-3xl md:text-4xl">
                            {latestPost
                                ? latestPost.title
                                : t('home.slider.news_fallback_title')}
                        </h2>
                        <p className="line-clamp-2 max-w-xl text-xs leading-relaxed text-slate-300 sm:line-clamp-3 sm:text-sm">
                            {latestPost
                                ? latestPost.excerpt
                                : t('home.slider.news_fallback_text')}
                        </p>
                        <div className="xs:w-auto mt-2 w-full sm:mt-4">
                            {latestPost && latestPost.slug ? (
                                <Button
                                    className="xs:w-auto w-full rounded-full bg-blue-600 px-5 py-2 text-xs font-bold text-white shadow-md transition-all hover:scale-105 hover:bg-blue-700 sm:px-8 sm:py-3 sm:text-sm"
                                    asChild
                                >
                                    <Link
                                        href={
                                            newsShow({
                                                locale,
                                                slug: latestPost.slug,
                                            }).url
                                        }
                                    >
                                        {t('home.slider.news_read_more')}
                                    </Link>
                                </Button>
                            ) : (
                                <Button
                                    className="xs:w-auto w-full rounded-full bg-blue-600 px-5 py-2 text-xs font-bold text-white shadow-md transition-all hover:scale-105 hover:bg-blue-700 sm:px-8 sm:py-3 sm:text-sm"
                                    asChild
                                >
                                    <Link href={newsIndex({ locale }).url}>
                                        {t('home.slider.news_feed')}
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </div>
                </div>

                {/* SLIDE 3: TOURIST GROUP REGISTRATION */}
                <div
                    className={`absolute inset-0 flex h-full w-full items-center transition-all duration-700 ease-in-out ${
                        current === 2
                            ? 'z-10 translate-x-0 opacity-100'
                            : 'pointer-events-none z-0 translate-x-full opacity-0'
                    }`}
                >
                    <div className="absolute inset-0 z-1 bg-gradient-to-r from-emerald-950/90 via-slate-950/85 to-slate-900/60" />
                    <div className="absolute inset-0 z-1 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-emerald-600/10 via-transparent to-transparent" />

                    <div className="xs:px-8 relative z-10 flex max-w-xl flex-col items-start gap-2 px-5 py-6 text-left sm:max-w-3xl sm:gap-4 sm:px-16 sm:py-0">
                        <div className="flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-600/10 px-3 py-0.5 text-[10px] font-semibold tracking-wider text-emerald-400 uppercase sm:text-xs">
                            <Users className="size-3.5" />
                            {t('home.slider.tourism_badge')}
                        </div>
                        <h2 className="xs:text-2xl text-xl leading-tight font-extrabold tracking-tight sm:text-4xl md:text-5xl">
                            {t('home.slider.tourism_title')}
                        </h2>
                        <p className="xs:line-clamp-none line-clamp-3 max-w-xl text-xs leading-relaxed text-slate-300 sm:text-sm md:text-base">
                            {t('home.slider.tourism_text')}
                        </p>
                        <div className="xs:flex-row xs:w-auto mt-2 flex w-full flex-col gap-2 sm:mt-4 sm:gap-4">
                            <Button
                                className="xs:w-auto w-full rounded-full bg-emerald-600 px-5 py-2 text-xs font-bold text-white shadow-md transition-all hover:scale-105 hover:bg-emerald-700 sm:px-8 sm:py-3 sm:text-sm"
                                asChild
                            >
                                <Link
                                    href={touristGroupsCreate({ locale }).url}
                                >
                                    {t('home.slider.tourism_register')}
                                </Link>
                            </Button>
                            <Button
                                variant="outline"
                                className="xs:w-auto w-full rounded-full border-slate-700 bg-slate-900/40 px-5 py-2 text-xs text-white hover:bg-slate-800 sm:px-8 sm:py-3 sm:text-sm"
                                asChild
                            >
                                <Link href={newsIndex({ locale }).url}>
                                    {t('home.slider.tourism_situation')}
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Slider Navigation Arrows */}
            <button
                onClick={prevSlide}
                className="absolute top-1/2 left-4 z-20 flex size-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/10 bg-slate-950/30 text-white/70 opacity-0 transition-all group-hover:opacity-100 hover:bg-slate-950/60 hover:text-white focus-visible:opacity-100 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                aria-label={t('home.slider.prev')}
            >
                <ChevronLeft className="size-6" />
            </button>
            <button
                onClick={nextSlide}
                className="absolute top-1/2 right-4 z-20 flex size-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full border border-white/10 bg-slate-950/30 text-white/70 opacity-0 transition-all group-hover:opacity-100 hover:bg-slate-950/60 hover:text-white focus-visible:opacity-100 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                aria-label={t('home.slider.next')}
            >
                <ChevronRight className="size-6" />
            </button>

            {/* Slide Indicators (Dots) */}
            <div className="absolute bottom-6 left-1/2 z-20 flex -translate-x-1/2 gap-2.5">
                {Array.from({ length: slidesCount }).map((_, idx) => (
                    <button
                        key={idx}
                        onClick={() => setCurrent(idx)}
                        className={`size-2.5 cursor-pointer rounded-full transition-all focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900 focus-visible:outline-none ${
                            current === idx
                                ? 'w-6 bg-blue-500'
                                : 'bg-white/40 hover:bg-white/70'
                        }`}
                        aria-label={t('home.slider.go_to', { number: idx + 1 })}
                        aria-current={current === idx}
                    />
                ))}
            </div>
        </section>
    );
}
