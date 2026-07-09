import { ExternalLink } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { AppEmblem } from '@/components/app-emblem';
import { useTranslations } from '@/hooks/use-translations';

const SLIDE_HEIGHT =
    'min-h-[300px] h-[300px] sm:min-h-[400px] sm:h-[400px] lg:min-h-[460px] lg:h-[460px]';

/**
 * "Leader of the Nation" portrait card beside the homepage news slider. The whole card opens
 * president.tj in a new tab (standard on Tajik government portals).
 */
export function PresidentCard() {
    const { t } = useTranslations();
    const { president } = usePage().props as {
        president?: { url: string; photo: string };
    };

    const presidentUrl = president?.url ?? 'https://president.tj';
    const photoSrc = president?.photo ?? '/images/president.webp';

    const [hasPhoto, setHasPhoto] = useState(true);

    return (
        <a
            href={presidentUrl}
            target="_blank"
            rel="noopener noreferrer"
            aria-label={`${t('home.president.kicker')} — ${t('home.president.name')}, president.tj`}
            className={`group relative block overflow-hidden rounded-2xl border border-border bg-brand shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none ${SLIDE_HEIGHT}`}
        >
            {hasPhoto ? (
                <img
                    src={photoSrc}
                    alt=""
                    onError={() => setHasPhoto(false)}
                    className="absolute inset-0 h-full w-full object-cover object-top transition-transform duration-500 group-hover:scale-[1.02]"
                />
            ) : (
                <div className="absolute inset-0 flex items-center justify-center bg-brand">
                    <AppEmblem className="size-24 text-white/10" />
                </div>
            )}

            <div
                className="absolute inset-0 bg-gradient-to-t from-black/90 via-black/25 to-transparent"
                aria-hidden="true"
            />

            <div className="absolute inset-x-0 bottom-0 p-5 sm:p-6">
                <span className="text-[11px] font-semibold tracking-wider text-white/80 uppercase">
                    {t('home.president.kicker')}
                </span>
                <span className="mt-1 flex items-center gap-1.5 text-base leading-tight font-bold text-white sm:text-lg">
                    {t('home.president.name')}
                    <ExternalLink
                        className="size-3.5 shrink-0 opacity-70 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        aria-hidden="true"
                    />
                </span>
                <span className="mt-1 block text-xs text-white/70 sm:text-sm">
                    {t('home.president.subtitle')}
                </span>
            </div>
        </a>
    );
}
