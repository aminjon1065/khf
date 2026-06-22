import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

export function formatDate(dateString: string | null | undefined, locale: string = 'tj'): string {
    if (!dateString) return '';

    let date = new Date(dateString);
    if (isNaN(date.getTime())) {
        // Try parsing dd.mm.yyyy (e.g. 22.06.2026)
        const parts = dateString.split(/[\s.]+/);
        if (parts.length >= 3) {
            const day = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1;
            const year = parseInt(parts[2], 10);
            date = new Date(year, month, day);
        }
    }

    if (isNaN(date.getTime())) return dateString;

    // Use Intl.DateTimeFormat to localize month names, e.g. 15 января 2026
    const mappedLocale = locale === 'tj' ? 'tg-TJ' : locale === 'ru' ? 'ru-RU' : 'en-US';
    
    return new Intl.DateTimeFormat(mappedLocale, {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(date);
}
