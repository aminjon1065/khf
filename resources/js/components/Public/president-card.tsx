import { ExternalLink } from 'lucide-react';
import { useState } from 'react';
import { AppEmblem } from '@/components/app-emblem';
import { useTranslations } from '@/hooks/use-translations';

const PRESIDENT_URL = 'https://president.tj';
const PRESIDENT_PHOTO = '/images/president.webp';

/**
 * "Leader of the Nation" portrait card shown beside the homepage hero, linking out to the official
 * presidential portal — a standard element on Tajik government sites. Falls back to a branded panel
 * until the official portrait is placed at {@link PRESIDENT_PHOTO}, so it never renders broken.
 */
export function PresidentCard() {
    const { t } = useTranslations();
    const [hasPhoto, setHasPhoto] = useState(true);

    return (
        <a
            href={PRESIDENT_URL}
            target="_blank"
            rel="noopener noreferrer"
            aria-label={`${t('home.president.kicker')} — ${t('home.president.name')}, president.tj`}
            className="group relative block h-[240px] overflow-hidden rounded-2xl border border-border bg-brand shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none lg:h-full"
        >
            {hasPhoto ? (
                <img
                    src={PRESIDENT_PHOTO}
                    alt=""
                    onError={() => setHasPhoto(false)}
                    className="absolute inset-0 h-full w-full object-cover object-top transition-transform duration-500 group-hover:scale-105"
                />
            ) : (
                <div className="absolute inset-0 flex items-center justify-center">
                    <AppEmblem className="size-24 text-white/10" />
                </div>
            )}

            <div
                className="absolute inset-0 bg-gradient-to-t from-black/85 via-black/35 to-transparent"
                aria-hidden="true"
            />

            <div className="absolute inset-x-0 bottom-0 p-5">
                <span className="text-[11px] font-semibold tracking-wider text-white/80 uppercase">
                    {t('home.president.kicker')}
                </span>
                <span className="mt-1 flex items-center gap-1.5 text-base leading-tight font-bold text-white">
                    {t('home.president.name')}
                    <ExternalLink
                        className="size-3.5 shrink-0 opacity-70 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        aria-hidden="true"
                    />
                </span>
                <span className="mt-1 block text-xs text-white/70">
                    {t('home.president.subtitle')}
                </span>
            </div>
        </a>
    );
}
