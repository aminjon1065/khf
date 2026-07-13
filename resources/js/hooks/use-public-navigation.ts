import { useTranslations } from '@/hooks/use-translations';
import { welcome } from '@/routes';
import { index as contactsIndex } from '@/routes/contacts';
import { index as documentsIndex } from '@/routes/documents';
import { index as guidesIndex } from '@/routes/guides';
import { index as leadershipIndex } from '@/routes/leadership';
import { index as newsIndex } from '@/routes/news';
import type { NavEntry, NavLeaf, PublicMenuItem } from '@/types/public-layout';

function mapMenuToNavEntry(item: PublicMenuItem): NavEntry | null {
    if (!item.title) {
        return null;
    }

    const visibleChildren = (item.children ?? []).filter(
        (child) => child.title,
    );

    if (visibleChildren.length > 0) {
        return {
            label: item.title,
            items: visibleChildren.map((child) => ({
                label: child.title as string,
                href: child.url || '#',
            })),
        };
    }

    return {
        label: item.title,
        href: item.url || '#',
    };
}

function pathOf(href: string): string {
    try {
        return new URL(href, 'http://h').pathname;
    } catch {
        return href;
    }
}

/**
 * Primary nav entries from CMS menu (with static fallback) + active-path helpers.
 */
export function usePublicNavigation(
    locale: string,
    currentUrl: string,
    rawPrimary: PublicMenuItem[],
) {
    const { t } = useTranslations();

    const navEntries: NavEntry[] = rawPrimary
        .map(mapMenuToNavEntry)
        .filter((entry): entry is NavEntry => entry !== null);

    if (navEntries.length === 0) {
        navEntries.push(
            { label: t('nav.home'), href: welcome({ locale }).url },
            { label: t('nav.about'), href: leadershipIndex({ locale }).url },
            { label: t('nav.news'), href: newsIndex({ locale }).url },
            { label: t('nav.guides'), href: guidesIndex({ locale }).url },
            { label: t('nav.documents'), href: documentsIndex({ locale }).url },
            { label: t('nav.contacts'), href: contactsIndex({ locale }).url },
        );
    }

    const homePath = pathOf(welcome({ locale }).url);

    const isActive = (href: string) => {
        const target = pathOf(href);
        const current = pathOf(currentUrl);

        if (target === homePath) {
            return current === homePath;
        }

        return current === target || current.startsWith(`${target}/`);
    };

    const groupActive = (items: NavLeaf[]) =>
        items.some((item) => isActive(item.href));

    return { navEntries, isActive, groupActive };
}
