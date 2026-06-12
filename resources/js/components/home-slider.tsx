import { useEffect, useState, useRef } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Phone, ShieldAlert, MapPin, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { AppEmblem } from '@/components/app-emblem';
import { index as newsIndex, show as newsShow } from '@/routes/news';
import { create as touristGroupsCreate } from '@/routes/tourist-groups';
import { index as guidesIndex } from '@/routes/guides';

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

    const latestPost = latestPosts && latestPosts.length > 0 ? latestPosts[0] : null;

    return (
        <section
            onMouseEnter={() => setIsHovered(true)}
            onMouseLeave={() => setIsHovered(false)}
            className="relative overflow-hidden rounded-2xl border border-border/50 bg-[#0f172a] text-white shadow-xl h-[420px] sm:h-[460px] group transition-all duration-700"
        >
            {/* Slide Wrapper */}
            <div className="relative w-full h-full">
                {/* SLIDE 1: EMERGENCY CALL HOTLINE */}
                <div
                    className={`absolute inset-0 w-full h-full flex items-center transition-all duration-700 ease-in-out ${
                        current === 0
                            ? 'opacity-100 translate-x-0 z-10'
                            : 'opacity-0 translate-x-full z-0 pointer-events-none'
                    }`}
                >
                    <div className="absolute inset-0 bg-gradient-to-r from-red-950/90 via-slate-950/80 to-slate-900/60 z-1" />
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-red-600/20 via-transparent to-transparent z-1" />
                    
                    <div className="relative z-10 max-w-3xl px-8 sm:px-16 flex flex-col items-start text-left gap-4">
                        <div className="flex items-center gap-3 bg-red-600/10 border border-red-500/30 rounded-full px-4 py-1 text-red-400 text-xs font-semibold uppercase tracking-wider">
                            <ShieldAlert className="size-4 animate-pulse" />
                            {t('home.hero.emergency_call')}
                        </div>
                        <h2 className="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight">
                            Единая служба спасения КЧС Таджикистана
                        </h2>
                        <p className="text-slate-300 text-sm sm:text-base max-w-xl leading-relaxed">
                            Если вашей жизни угрожает опасность, или вы стали свидетелем чрезвычайной ситуации — незамедлительно звоните по бесплатному номеру 112. Мы работаем круглосуточно.
                        </p>
                        <div className="mt-4 flex flex-wrap gap-4">
                            <a
                                href="tel:112"
                                className="inline-flex items-center gap-3 rounded-full bg-red-600 hover:bg-red-700 text-white px-8 py-3 text-sm font-bold shadow-md transition-all hover:scale-105"
                            >
                                <Phone className="size-4 fill-white" />
                                Позвонить 112
                            </a>
                            <Button variant="outline" className="rounded-full bg-slate-900/40 text-white border-slate-700 hover:bg-slate-800" asChild>
                                <Link href={guidesIndex({ locale }).url}>
                                    Инструкции по безопасности
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                {/* SLIDE 2: LATEST PUBLICATION */}
                <div
                    className={`absolute inset-0 w-full h-full flex items-center transition-all duration-700 ease-in-out ${
                        current === 1
                            ? 'opacity-100 translate-x-0 z-10'
                            : 'opacity-0 translate-x-full z-0 pointer-events-none'
                    }`}
                >
                    {latestPost && latestPost.cover_url ? (
                        <div
                            className="absolute inset-0 bg-cover bg-center transition-transform duration-10000 ease-linear transform scale-105"
                            style={{ backgroundImage: `url(${latestPost.cover_url})` }}
                        />
                    ) : (
                        <div className="absolute inset-0 bg-gradient-to-r from-blue-950 via-slate-900 to-slate-800" />
                    )}
                    <div className="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-950/80 to-slate-950/30 z-1" />

                    <div className="relative z-10 max-w-3xl px-8 sm:px-16 flex flex-col items-start text-left gap-4">
                        <div className="flex items-center gap-3 bg-blue-500/10 border border-blue-400/30 rounded-full px-4 py-1 text-blue-400 text-xs font-semibold uppercase tracking-wider">
                            <AppEmblem className="size-4" />
                            Главная новость
                        </div>
                        <h2 className="text-2xl sm:text-4xl font-extrabold tracking-tight leading-tight line-clamp-2">
                            {latestPost ? latestPost.title : 'Официальный портал КЧС Таджикистана'}
                        </h2>
                        <p className="text-slate-300 text-xs sm:text-sm max-w-xl leading-relaxed line-clamp-3">
                            {latestPost ? latestPost.excerpt : 'Будьте в курсе последних оперативных новостей, штормовых предупреждений и рекомендаций Комитета по чрезвычайным ситуациям.'}
                        </p>
                        <div className="mt-4">
                            {latestPost && latestPost.slug ? (
                                <Button className="rounded-full bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 text-sm font-bold shadow-md transition-all hover:scale-105" asChild>
                                    <Link href={newsShow({ locale, slug: latestPost.slug }).url}>
                                        Читать подробнее
                                    </Link>
                                </Button>
                            ) : (
                                <Button className="rounded-full bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 text-sm font-bold shadow-md transition-all hover:scale-105" asChild>
                                    <Link href={newsIndex({ locale }).url}>
                                        Лента новостей
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </div>
                </div>

                {/* SLIDE 3: TOURIST GROUP REGISTRATION */}
                <div
                    className={`absolute inset-0 w-full h-full flex items-center transition-all duration-700 ease-in-out ${
                        current === 2
                            ? 'opacity-100 translate-x-0 z-10'
                            : 'opacity-0 translate-x-full z-0 pointer-events-none'
                    }`}
                >
                    <div className="absolute inset-0 bg-gradient-to-r from-emerald-950/90 via-slate-950/85 to-slate-900/60 z-1" />
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-emerald-600/10 via-transparent to-transparent z-1" />

                    <div className="relative z-10 max-w-3xl px-8 sm:px-16 flex flex-col items-start text-left gap-4">
                        <div className="flex items-center gap-3 bg-emerald-600/10 border border-emerald-500/30 rounded-full px-4 py-1 text-emerald-400 text-xs font-semibold uppercase tracking-wider">
                            <Users className="size-4" />
                            Безопасный туризм
                        </div>
                        <h2 className="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight">
                            Регистрация туристических групп
                        </h2>
                        <p className="text-slate-300 text-sm sm:text-base max-w-xl leading-relaxed">
                            Планируете поход по горным маршрутам Таджикистана? Зарегистрируйте свою группу в КЧС. Это позволит спасателям оперативно оказать помощь в случае происшествия.
                        </p>
                        <div className="mt-4 flex flex-wrap gap-4">
                            <Button className="rounded-full bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3 text-sm font-bold shadow-md transition-all hover:scale-105" asChild>
                                <Link href={touristGroupsCreate({ locale }).url}>
                                    Зарегистрировать группу
                                </Link>
                            </Button>
                            <Button variant="outline" className="rounded-full bg-slate-900/40 text-white border-slate-700 hover:bg-slate-800" asChild>
                                <Link href={newsIndex({ locale }).url}>
                                    Оперативная обстановка
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Slider Navigation Arrows */}
            <button
                onClick={prevSlide}
                className="absolute left-4 top-1/2 -translate-y-1/2 flex items-center justify-center size-10 rounded-full bg-slate-950/30 text-white/70 border border-white/10 hover:bg-slate-950/60 hover:text-white transition-all opacity-0 group-hover:opacity-100 cursor-pointer z-20"
                aria-label="Предыдущий слайд"
            >
                <ChevronLeft className="size-6" />
            </button>
            <button
                onClick={nextSlide}
                className="absolute right-4 top-1/2 -translate-y-1/2 flex items-center justify-center size-10 rounded-full bg-slate-950/30 text-white/70 border border-white/10 hover:bg-slate-950/60 hover:text-white transition-all opacity-0 group-hover:opacity-100 cursor-pointer z-20"
                aria-label="Следующий слайд"
            >
                <ChevronRight className="size-6" />
            </button>

            {/* Slide Indicators (Dots) */}
            <div className="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2.5 z-20">
                {Array.from({ length: slidesCount }).map((_, idx) => (
                    <button
                        key={idx}
                        onClick={() => setCurrent(idx)}
                        className={`size-2.5 rounded-full transition-all cursor-pointer ${
                            current === idx
                                ? 'bg-blue-500 w-6'
                                : 'bg-white/40 hover:bg-white/70'
                        }`}
                        aria-label={`Перейти к слайду ${idx + 1}`}
                    />
                ))}
            </div>
        </section>
    );
}
