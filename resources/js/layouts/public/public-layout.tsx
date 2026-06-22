import { Link, usePage } from '@inertiajs/react';
import {
    ChevronDown,
    ExternalLink,
    Eye,
    Menu,
    Phone,
    Search,
} from 'lucide-react';
import { useState } from 'react';
import { AccessibilityToolbar } from '@/components/accessibility-toolbar';
import { AdminBar } from '@/components/admin-bar';
import { AlertBanner } from '@/components/alert-banner';
import { AppEmblem } from '@/components/app-emblem';
import { LanguageSwitcher } from '@/components/language-switcher';
import { BottomNavigation } from '@/components/Public/bottom-navigation';
import { GlobalSearchModal } from '@/components/Public/GlobalSearchModal';
import { TajikistanEmblem } from '@/components/Public/symbols/state-emblem';
import { TajikistanFlag } from '@/components/Public/symbols/state-flag';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';
import { welcome } from '@/routes';
import { create as appealsCreate } from '@/routes/appeals';
import { index as contactsIndex } from '@/routes/contacts';
import { index as documentsIndex } from '@/routes/documents';
import { index as faqIndex } from '@/routes/faq';
import { index as galleryIndex } from '@/routes/gallery';
import { index as guidesIndex } from '@/routes/guides';
import { index as incidentsIndex } from '@/routes/incidents';
import { index as leadershipIndex } from '@/routes/leadership';
import { index as mapIndex } from '@/routes/map';
import { index as newsIndex } from '@/routes/news';
import { show as pageShow } from '@/routes/pages';
import { index as statisticsIndex } from '@/routes/statistics';
import { index as structureIndex } from '@/routes/structure';
import { create as subscriptionsCreate } from '@/routes/subscriptions';
import { index as tendersIndex } from '@/routes/tenders';
import { create as touristGroupsCreate } from '@/routes/tourist-groups';
import { index as vacanciesIndex } from '@/routes/vacancies';

type NavLeaf = { label: string; href: string };
type NavEntry = NavLeaf | { label: string; items: NavLeaf[] };

export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const props = usePage().props;
    const currentUrl = usePage().url;
    // Defensive defaults: error pages for unmatched URLs may render before shared props are set.
    const locale = props.locale ?? 'tj';
    const auth = props.auth;
    const navPages = props.navPages ?? [];
    const { t } = useTranslations();
    const [isSearchOpen, setIsSearchOpen] = useState(false);
    const [isA11yOpen, setIsA11yOpen] = useState(false);
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

    const locales =
        (props.locales as Array<{
            code: string;
            native_name: string;
            hreflang: string;
        }>) ?? [];
    const localeSwitch = (props.localeSwitch as Record<string, string>) ?? {};

    // Check for critical alerts to trigger Red State header
    const activeAlerts = (props.activeAlerts as Array<{ level: string }>) ?? [];
    const isRedState = activeAlerts.some((a) => a.level === 'critical');

    const headerClass = isRedState
        ? 'bg-red-900 text-white border-red-700'
        : 'bg-card text-foreground border-border';

    const buttonClass = isRedState
        ? 'rounded-md p-2 text-red-100 transition-colors hover:bg-red-800 hover:text-white cursor-pointer'
        : 'rounded-md p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground cursor-pointer';

    // Admin privileges check for WordPress-style AdminBar
    const canManage =
        auth?.user &&
        (auth.roles?.includes('super-admin') ||
            auth.roles?.includes('moderator') ||
            auth.permissions?.includes('posts.manage') ||
            auth.permissions?.includes('pages.manage'));

    const pageId = (props.page as { id?: number })?.id;
    const postId = (props.post as { id?: number })?.id;

    const menus = (props.menus as Record<string, any[]>) ?? {};
    const rawPrimary = menus.primary ?? [];
    const rawFooter = menus.footer ?? [];

    const mapMenuToNavEntry = (item: any): NavEntry => {
        if (item.children && item.children.length > 0) {
            return {
                label: item.title,
                items: item.children.map((child: any) => ({
                    label: child.title,
                    href: child.url || '#',
                })),
            };
        }
        return {
            label: item.title,
            href: item.url || '#',
        };
    };

    const navEntries: NavEntry[] = rawPrimary.map(mapMenuToNavEntry);

    // Fallback if no primary menu is seeded yet
    if (navEntries.length === 0) {
        navEntries.push({ label: t('nav.home'), href: welcome({ locale }).url });
    }

    const pathOf = (href: string) => {
        try {
            return new URL(href, 'http://h').pathname;
        } catch {
            return href;
        }
    };
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
        items.some((i) => isActive(i.href));

    const navTrigger =
        'inline-flex cursor-pointer items-center gap-1 rounded-md px-3.5 py-2 text-sm font-medium text-foreground/70 transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';
    const navActive = 'bg-primary/10 text-primary';

    return (
        <div className="flex min-h-screen flex-col bg-background font-sans text-foreground antialiased selection:bg-primary/20">
            <a
                href="#main-content"
                className="sr-only rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-[100]"
            >
                {t('a11y.skip_to_content')}
            </a>
            {canManage && <AdminBar pageId={pageId} postId={postId} />}
            {isA11yOpen && (
                <AccessibilityToolbar onClose={() => setIsA11yOpen(false)} />
            )}

            {/* Brand signature rule (ТЗ §6.1) */}
            <div className="h-[3px] bg-signal print:hidden" />

            {/* Government utility strip — state symbols + identifier, language + search (ТЗ §31a) */}
            <div className="border-b border-border bg-card text-muted-foreground print:hidden">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-1.5 text-xs">
                    <div className="flex min-w-0 items-center gap-2.5">
                        <TajikistanEmblem className="size-5 shrink-0" />
                        <TajikistanFlag className="h-3.5 w-7 shrink-0 rounded-xs" />
                        <span className="truncate">
                            {t('govbar.identifier')}
                        </span>
                    </div>
                    <div className="flex shrink-0 items-center gap-1">
                        <LanguageSwitcher className="text-muted-foreground hover:text-foreground" />
                        <span className="mx-1 hidden h-3 w-px bg-border sm:inline" />
                        <button
                            onClick={() => setIsSearchOpen(true)}
                            className="inline-flex cursor-pointer items-center gap-1.5 rounded-md px-2 py-1 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                            aria-label={t('a11y.site_search')}
                        >
                            <Search className="size-3.5" aria-hidden="true" />
                            <span className="hidden sm:inline">
                                {t('actions.search')}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <AlertBanner />

            <header
                className={`sticky top-0 z-40 border-b transition-colors duration-300 print:hidden ${headerClass}`}
            >
                {/* Main bar: identity · official title · hotline + controls */}
                <div className="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3.5">
                    <Link
                        href={welcome({ locale }).url}
                        className="flex shrink-0 items-center gap-3 transition-opacity hover:opacity-80"
                    >
                        <AppEmblem
                            alt=""
                            className="size-10 shrink-0 md:size-11"
                        />
                        <span className="text-lg font-bold tracking-tight md:text-xl">
                            {t('site.short_name')}
                        </span>
                    </Link>

                    <div className="mx-auto hidden max-w-md flex-1 px-4 text-center lg:block">
                        <h1 className="text-xs leading-snug font-bold tracking-tight text-balance text-muted-foreground uppercase">
                            {t('site.full_name')}
                        </h1>
                    </div>

                    <div className="ml-auto flex items-center gap-2 lg:ml-0">
                        <a
                            href="tel:112"
                            className={`inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold transition-colors ${
                                isRedState
                                    ? 'bg-white text-red-700 hover:bg-white/90'
                                    : 'border border-signal/30 text-signal hover:bg-signal/10'
                            }`}
                        >
                            <Phone className="size-4" aria-hidden="true" />
                            <span className="hidden tabular-nums sm:inline">
                                {t('home.hero.emergency_call')}
                            </span>
                            <span className="tabular-nums sm:hidden">112</span>
                        </a>

                        <button
                            onClick={() => setIsA11yOpen(!isA11yOpen)}
                            className={buttonClass}
                            aria-label={t('a11y.open')}
                            title={t('a11y.open')}
                            aria-expanded={isA11yOpen}
                        >
                            <Eye className="size-5" />
                        </button>

                        {/* Hamburger drawer (mobile + tablet, below lg) */}
                        <div className="lg:hidden">
                            <Sheet
                                open={isMobileMenuOpen}
                                onOpenChange={setIsMobileMenuOpen}
                            >
                                <SheetTrigger asChild>
                                    <button
                                        className={buttonClass}
                                        aria-label={t('a11y.menu')}
                                    >
                                        <Menu
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    </button>
                                </SheetTrigger>
                                <SheetContent
                                    side="right"
                                    className="flex w-full max-w-xs flex-col gap-4 border-slate-800 bg-[#0b1220] p-6 text-white sm:max-w-sm"
                                >
                                    <SheetHeader className="flex justify-start border-b border-slate-800 pb-4 text-left">
                                        <SheetTitle className="flex items-center gap-2 text-lg font-bold text-white">
                                            <AppEmblem
                                                alt=""
                                                className="size-8 shrink-0"
                                            />
                                            <span className="truncate">
                                                {t('site.short_name')}
                                            </span>
                                        </SheetTitle>
                                    </SheetHeader>
                                    <nav
                                        aria-label={t('a11y.menu')}
                                        className="flex flex-1 flex-col gap-0.5 overflow-y-auto text-base"
                                    >
                                        {navEntries.map((entry) =>
                                            'items' in entry ? (
                                                <div
                                                    key={entry.label}
                                                    className="flex flex-col"
                                                >
                                                    <span className="px-3 pt-4 pb-1 text-xs font-semibold tracking-wider text-slate-500 uppercase">
                                                        {entry.label}
                                                    </span>
                                                    {entry.items.map((sub) => (
                                                        <Link
                                                            key={sub.href}
                                                            href={sub.href}
                                                            onClick={() =>
                                                                setIsMobileMenuOpen(
                                                                    false,
                                                                )
                                                            }
                                                            className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                                                        >
                                                            {sub.label}
                                                        </Link>
                                                    ))}
                                                </div>
                                            ) : (
                                                <Link
                                                    key={entry.href}
                                                    href={entry.href}
                                                    onClick={() =>
                                                        setIsMobileMenuOpen(
                                                            false,
                                                        )
                                                    }
                                                    className="rounded-md px-3 py-2 font-semibold text-slate-100 transition-colors hover:bg-slate-800 hover:text-white"
                                                >
                                                    {entry.label}
                                                </Link>
                                            ),
                                        )}

                                        <div className="my-3 border-t border-slate-800" />

                                        <button
                                            onClick={() => {
                                                setIsMobileMenuOpen(false);
                                                setIsSearchOpen(true);
                                            }}
                                            className="flex w-full cursor-pointer items-center gap-3 rounded-md px-3 py-2 text-left text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                                        >
                                            <Search className="size-4.5" />
                                            <span>{t('a11y.site_search')}</span>
                                        </button>

                                        <button
                                            onClick={() => {
                                                setIsMobileMenuOpen(false);
                                                setIsA11yOpen(true);
                                            }}
                                            className="flex w-full cursor-pointer items-center gap-3 rounded-md px-3 py-2 text-left text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                                        >
                                            <Eye className="size-4.5" />
                                            <span>{t('a11y.open')}</span>
                                        </button>

                                        {locales.length > 1 && (
                                            <>
                                                <div className="my-3 border-t border-slate-800" />
                                                <div className="flex flex-col gap-2 px-3">
                                                    <span className="text-xs font-semibold tracking-wider text-slate-500 uppercase">
                                                        {t('lang.label')}
                                                    </span>
                                                    <div className="flex w-full rounded-md border border-slate-700 bg-slate-800 p-0.5">
                                                        {locales.map(
                                                            (language) => (
                                                                <Link
                                                                    key={
                                                                        language.code
                                                                    }
                                                                    href={
                                                                        localeSwitch[
                                                                            language
                                                                                .code
                                                                        ] ?? '#'
                                                                    }
                                                                    hrefLang={
                                                                        language.hreflang
                                                                    }
                                                                    lang={
                                                                        language.hreflang
                                                                    }
                                                                    onClick={() =>
                                                                        setIsMobileMenuOpen(
                                                                            false,
                                                                        )
                                                                    }
                                                                    className={`flex-1 rounded py-1.5 text-center text-xs font-semibold transition-all ${
                                                                        language.code ===
                                                                        locale
                                                                            ? 'bg-primary font-bold text-primary-foreground shadow-sm'
                                                                            : 'text-slate-400 hover:text-white'
                                                                    }`}
                                                                >
                                                                    {
                                                                        language.native_name
                                                                    }
                                                                </Link>
                                                            ),
                                                        )}
                                                    </div>
                                                </div>
                                            </>
                                        )}
                                    </nav>
                                </SheetContent>
                            </Sheet>
                        </div>
                    </div>
                </div>

                {/* Primary grouped navigation (desktop, lg+) — ТЗ §31(b) */}
                <div className="hidden border-t border-border bg-card lg:block">
                    <nav
                        aria-label={t('a11y.primary_nav')}
                        className="mx-auto flex max-w-6xl items-center gap-0.5 px-4 py-2"
                    >
                        {navEntries.map((entry) =>
                            'items' in entry ? (
                                <DropdownMenu key={entry.label}>
                                    <DropdownMenuTrigger
                                        className={cn(
                                            navTrigger,
                                            groupActive(entry.items) &&
                                                navActive,
                                        )}
                                    >
                                        {entry.label}
                                        <ChevronDown
                                            className="size-4 opacity-60"
                                            aria-hidden="true"
                                        />
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align="start"
                                        className="min-w-56"
                                    >
                                        {entry.items.map((sub) => (
                                            <DropdownMenuItem
                                                key={sub.href}
                                                asChild
                                            >
                                                <Link
                                                    href={sub.href}
                                                    className={cn(
                                                        'w-full cursor-pointer',
                                                        isActive(sub.href) &&
                                                            'text-primary',
                                                    )}
                                                >
                                                    {sub.label}
                                                </Link>
                                            </DropdownMenuItem>
                                        ))}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            ) : (
                                <Link
                                    key={entry.href}
                                    href={entry.href}
                                    className={cn(
                                        navTrigger,
                                        isActive(entry.href) && navActive,
                                    )}
                                >
                                    {entry.label}
                                </Link>
                            ),
                        )}
                    </nav>
                </div>
            </header>

            <main
                id="main-content"
                tabIndex={-1}
                className="mx-auto w-full max-w-6xl flex-1 px-4 py-10 focus:outline-none sm:py-16"
            >
                {children}
            </main>

            <footer className="bg-brand-strong text-brand-strong-foreground print:hidden">
                <div className="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
                    {/* Agency identity & Visitor Stats */}
                    <div className="flex flex-col gap-5 sm:col-span-2 lg:col-span-1">
                        <div className="flex flex-col gap-3">
                            <div className="flex items-center gap-2.5">
                                <AppEmblem
                                    alt=""
                                    className="size-10 shrink-0"
                                />
                                <span className="text-base font-bold">
                                    {t('site.short_name')}
                                </span>
                            </div>
                            <p className="text-sm leading-relaxed text-brand-strong-foreground/70">
                                {t('site.full_name')}
                            </p>
                            <a
                                href="tel:112"
                                className="inline-flex w-fit items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-sm font-semibold transition-colors hover:bg-white/15"
                            >
                                <Phone className="size-4" aria-hidden="true" />
                                {t('footer.hotline')}: 112
                            </a>
                        </div>

                        {/* Visitor Statistics */}
                        <div className="flex flex-col gap-2 rounded-lg border border-white/10 bg-white/5 p-4 text-xs">
                            <p className="font-semibold tracking-wider text-brand-strong-foreground/50 uppercase">
                                {t('footer.statistics')}
                            </p>
                            <div className="grid grid-cols-3 gap-2 text-center text-brand-strong-foreground/80">
                                <div className="rounded border border-white/5 bg-white/5 py-2">
                                    <span className="block text-sm font-bold text-white tabular-nums sm:text-base">
                                        1,482
                                    </span>
                                    <span className="text-[10px] text-brand-strong-foreground/60 uppercase">
                                        {t('footer.stats_today', { count: '' })
                                            .replace(': ', '')
                                            .trim()}
                                    </span>
                                </div>
                                <div className="rounded border border-white/5 bg-white/5 py-2">
                                    <span className="block text-sm font-bold text-white tabular-nums sm:text-base">
                                        42,918
                                    </span>
                                    <span className="text-[10px] text-brand-strong-foreground/60 uppercase">
                                        {t('footer.stats_month', { count: '' })
                                            .replace(': ', '')
                                            .trim()}
                                    </span>
                                </div>
                                <div className="rounded border border-white/5 bg-white/5 py-2">
                                    <span className="block text-sm font-bold text-white tabular-nums sm:text-base">
                                        518,402
                                    </span>
                                    <span className="text-[10px] text-brand-strong-foreground/60 uppercase">
                                        {t('footer.stats_year', { count: '' })
                                            .replace(': ', '')
                                            .trim()}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Sections */}
                    <nav
                        aria-label={t('a11y.footer_nav')}
                        className="flex flex-col gap-3"
                    >
                        <p className="text-xs font-semibold tracking-wider text-brand-strong-foreground/50 uppercase">
                            {t('footer.sections')}
                        </p>
                        <div className="flex flex-col gap-2 text-sm text-brand-strong-foreground/80">
                            {rawFooter.map((item) => (
                                <Link
                                    key={item.id}
                                    href={item.url || '#'}
                                    className="transition-colors hover:text-white"
                                >
                                    {item.title}
                                </Link>
                            ))}
                        </div>
                    </nav>

                    {/* Emergency numbers */}
                    <div className="flex flex-col gap-3">
                        <p className="text-xs font-semibold tracking-wider text-brand-strong-foreground/50 uppercase">
                            {t('contacts.emergency_numbers')}
                        </p>
                        <ul className="flex flex-col gap-2 text-sm text-brand-strong-foreground/80">
                            <li className="flex items-center justify-between gap-3">
                                <span>{t('contacts.helpline')}</span>
                                <a
                                    href="tel:112"
                                    className="font-bold tabular-nums hover:text-white"
                                >
                                    112
                                </a>
                            </li>
                            <li className="flex items-center justify-between gap-3">
                                <span>{t('contacts.fire')}</span>
                                <a
                                    href="tel:101"
                                    className="font-bold tabular-nums hover:text-white"
                                >
                                    101
                                </a>
                            </li>
                            <li className="flex items-center justify-between gap-3">
                                <span>{t('contacts.police')}</span>
                                <a
                                    href="tel:102"
                                    className="font-bold tabular-nums hover:text-white"
                                >
                                    102
                                </a>
                            </li>
                            <li className="flex items-center justify-between gap-3">
                                <span>{t('contacts.ambulance')}</span>
                                <a
                                    href="tel:103"
                                    className="font-bold tabular-nums hover:text-white"
                                >
                                    103
                                </a>
                            </li>
                        </ul>
                    </div>

                    {/* Useful resources */}
                    <div className="flex flex-col gap-3">
                        <p className="text-xs font-semibold tracking-wider text-brand-strong-foreground/50 uppercase">
                            {t('footer.useful_resources')}
                        </p>
                        <ul className="flex flex-col gap-2 text-sm text-brand-strong-foreground/80">
                            <li>
                                <a
                                    href="https://president.tj"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1.5 transition-colors hover:text-white"
                                >
                                    {t('footer.president')}
                                    <ExternalLink
                                        className="size-3 opacity-50"
                                        aria-hidden="true"
                                    />
                                </a>
                            </li>
                            <li>
                                <a
                                    href="https://government.tj"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1.5 transition-colors hover:text-white"
                                >
                                    {t('footer.government')}
                                    <ExternalLink
                                        className="size-3 opacity-50"
                                        aria-hidden="true"
                                    />
                                </a>
                            </li>
                            <li>
                                <a
                                    href="https://egov.tj"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1.5 transition-colors hover:text-white"
                                >
                                    {t('footer.egov')}
                                    <ExternalLink
                                        className="size-3 opacity-50"
                                        aria-hidden="true"
                                    />
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                {/* Legal / accessibility bar */}
                <div className="border-t border-white/10">
                    <div className="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-4 text-xs text-brand-strong-foreground/60 sm:flex-row sm:items-center sm:justify-between">
                        <span>
                            © {new Date().getFullYear()} {t('site.short_name')}{' '}
                            · {t('footer.rights')}
                        </span>
                        <div className="flex flex-wrap items-center gap-4">
                            <Link
                                href={
                                    pageShow({
                                        locale,
                                        slug: `privacy-policy-${locale}`,
                                    }).url
                                }
                                className="transition-colors hover:text-white"
                            >
                                {t('footer.privacy_policy')}
                            </Link>
                            <button
                                type="button"
                                onClick={() => setIsA11yOpen(true)}
                                className="inline-flex items-center gap-1.5 transition-colors hover:text-white"
                            >
                                <Eye className="size-3.5" aria-hidden="true" />
                                {t('footer.accessibility')}
                            </button>
                            <span className="rounded border border-white/20 px-2 py-0.5 font-medium">
                                WCAG 2.1 AA
                            </span>
                        </div>
                    </div>
                </div>
            </footer>

            <BottomNavigation />
            <GlobalSearchModal
                isOpen={isSearchOpen}
                setIsOpen={setIsSearchOpen}
            />
        </div>
    );
}
