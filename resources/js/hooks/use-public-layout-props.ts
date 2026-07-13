import { usePage } from '@inertiajs/react';
import type {
    PublicFooterContent,
    PublicLocale,
    PublicMenuItem,
} from '@/types/public-layout';

const defaultFooterContent: PublicFooterContent = {
    government_url: 'https://government.tj',
    egov_url: 'https://egov.tj',
    hotline: '112',
    copyright: null,
    resource_links: [],
};

/**
 * Shared public-layout props with defensive defaults (error pages may omit shared data).
 */
export function usePublicLayoutProps() {
    const props = usePage().props;
    const currentUrl = usePage().url;
    const locale = props.locale ?? 'tj';
    const auth = props.auth;

    const locales = (props.locales as PublicLocale[]) ?? [];
    const localeSwitch = (props.localeSwitch as Record<string, string>) ?? {};
    const socialLinks =
        (props.socialLinks as Array<{ platform: string; url: string }>) ?? [];
    const president = props.president as
        | { url: string; photo: string }
        | undefined;
    const footerContent =
        (props.footerContent as PublicFooterContent | undefined) ??
        defaultFooterContent;
    const hotline = footerContent.hotline || '112';

    const activeAlerts = (props.activeAlerts as Array<{ level: string }>) ?? [];
    const isRedState = activeAlerts.some((a) => a.level === 'critical');

    const headerClass = isRedState
        ? 'bg-red-900 text-white border-red-700'
        : 'bg-card text-foreground border-border';

    const buttonClass = isRedState
        ? 'rounded-md p-2 text-red-100 transition-colors hover:bg-red-800 hover:text-white cursor-pointer'
        : 'rounded-md p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground cursor-pointer';

    const canManage = Boolean(
        auth?.user &&
        (auth.roles?.includes('super-admin') ||
            auth.roles?.includes('moderator') ||
            auth.permissions?.includes('posts.manage') ||
            auth.permissions?.includes('pages.manage')),
    );

    const pageId = (props.page as { id?: number } | undefined)?.id;
    const postId = (props.post as { id?: number } | undefined)?.id;

    const menus = (props.menus as Record<string, PublicMenuItem[]>) ?? {};
    const rawPrimary = menus.primary ?? [];
    const rawFooter = menus.footer ?? [];

    return {
        locale,
        currentUrl,
        locales,
        localeSwitch,
        socialLinks,
        president,
        footerContent,
        hotline,
        isRedState,
        headerClass,
        buttonClass,
        canManage,
        pageId,
        postId,
        rawPrimary,
        rawFooter,
    };
}
