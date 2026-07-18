import { usePage } from '@inertiajs/react';

/**
 * Official КЧС / КҲФ circular emblem (civil-defense star, Tajik crown & stars, oak wreath, "1994").
 * One asset per language — the language-matched emblem is chosen from the active locale, falling
 * back to the Tajik (primary) version. The emblem is detailed; render it at ≥36px and never recolour
 * it (design system §4 / brand assets).
 */
const EMBLEMS: Record<string, string> = {
    tj: '/images/pwa-512.png',
    ru: '/images/pwa-512.png',
    en: '/images/pwa-512.png',
};

export function AppEmblem({
    className,
    alt = 'КЧС',
    locale,
}: {
    className?: string;
    alt?: string;
    locale?: string;
}) {
    const page = usePage().props;
    const active = locale ?? page.locale;
    const src = EMBLEMS[active] ?? EMBLEMS.tj;

    return (
        <img src={src} alt={alt} className={className} width={40} height={40} />
    );
}
