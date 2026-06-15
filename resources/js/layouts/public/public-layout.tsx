import { Link, usePage } from '@inertiajs/react';
import { ExternalLink, Eye, Menu, Phone, Search } from 'lucide-react';
import { useState } from 'react';
import { AccessibilityToolbar } from '@/components/accessibility-toolbar';
import { AdminBar } from '@/components/admin-bar';
import { AlertBanner } from '@/components/alert-banner';
import { AppEmblem } from '@/components/app-emblem';
import { LanguageSwitcher } from '@/components/language-switcher';
import { BottomNavigation } from '@/components/Public/bottom-navigation';
import { GlobalSearchModal } from '@/components/Public/GlobalSearchModal';
import { GovBar } from '@/components/Public/gov-bar';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useTranslations } from '@/hooks/use-translations';
import { welcome } from '@/routes';
import { create as appealsCreate } from '@/routes/appeals';
import { index as contactsIndex } from '@/routes/contacts';
import { index as documentsIndex } from '@/routes/documents';
import { index as guidesIndex } from '@/routes/guides';
import { index as incidentsIndex } from '@/routes/incidents';
import { index as mapIndex } from '@/routes/map';
import { index as newsIndex } from '@/routes/news';
import { show as pageShow } from '@/routes/pages';
import { create as subscriptionsCreate } from '@/routes/subscriptions';
import { create as touristGroupsCreate } from '@/routes/tourist-groups';

export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const props = usePage().props;
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
        ? 'bg-red-900 text-white border-red-700 shadow-lg'
        : 'bg-card text-foreground border-border shadow-xs';

    const linkClass = isRedState
        ? 'rounded-md px-3 py-2 text-red-100 hover:bg-red-800 hover:text-white transition-colors'
        : 'rounded-md px-3 py-2 text-muted-foreground hover:bg-muted hover:text-foreground transition-colors';

    const buttonClass = isRedState
        ? 'p-1.5 sm:p-2 rounded-md text-red-100 hover:bg-red-800 hover:text-white transition-colors cursor-pointer'
        : 'p-1.5 sm:p-2 rounded-md text-muted-foreground hover:bg-muted hover:text-foreground transition-colors cursor-pointer';

    const switcherClass = isRedState
        ? 'text-red-100 hover:bg-red-800 hover:text-white px-1.5 sm:px-3 text-xs sm:text-sm'
        : 'text-muted-foreground hover:bg-muted hover:text-foreground px-1.5 sm:px-3 text-xs sm:text-sm';

    // Admin privileges check for WordPress-style AdminBar
    const canManage =
        auth?.user &&
        (auth.roles?.includes('super-admin') ||
            auth.roles?.includes('moderator') ||
            auth.permissions?.includes('posts.manage') ||
            auth.permissions?.includes('pages.manage'));

    const pageId = (props.page as { id?: number })?.id;
    const postId = (props.post as { id?: number })?.id;

    return (
        <div className="flex min-h-screen flex-col bg-card font-sans text-foreground antialiased selection:bg-primary/20">
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
            <GovBar />
            <AlertBanner />
            <header
                className={`sticky top-0 z-50 border-b transition-all duration-500 print:hidden ${headerClass}`}
            >
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
                    <Link
                        href={welcome({ locale }).url}
                        className="xs:max-w-none flex max-w-[60%] items-center gap-2 transition-opacity hover:opacity-80"
                    >
                        <AppEmblem alt="" className="size-9 shrink-0 sm:size-10" />
                        <span
                            className={`truncate text-base font-bold tracking-tight sm:text-xl ${isRedState ? 'text-white' : 'text-foreground'}`}
                        >
                            {t('site.short_name')}
                        </span>
                    </Link>

                    {/* Desktop Navigation Links */}
                    <nav
                        aria-label={t('a11y.primary_nav')}
                        className="hidden items-center gap-1 text-sm font-medium sm:gap-2 lg:flex"
                    >
                        <Link
                            href={welcome({ locale }).url}
                            className={linkClass}
                        >
                            {t('nav.home')}
                        </Link>
                        <Link
                            href={newsIndex({ locale }).url}
                            className={linkClass}
                        >
                            {t('nav.news')}
                        </Link>
                        <Link
                            href={incidentsIndex({ locale }).url}
                            className={linkClass}
                        >
                            {t('nav.situation')}
                        </Link>
                        <Link
                            href={mapIndex({ locale }).url}
                            className={linkClass}
                        >
                            {t('nav.map')}
                        </Link>
                        <Link
                            href={documentsIndex({ locale }).url}
                            className={linkClass}
                        >
                            {t('nav.documents')}
                        </Link>
                        <Link
                            href={appealsCreate({ locale }).url}
                            className={linkClass}
                        >
                            {t('nav.reception')}
                        </Link>
                        <Link
                            href={touristGroupsCreate({ locale }).url}
                            className={linkClass}
                        >
                            {t('nav.tourism')}
                        </Link>
                        <Link
                            href={subscriptionsCreate({ locale }).url}
                            className={linkClass}
                        >
                            {t('nav.subscribe')}
                        </Link>
                    </nav>

                    {/* Controls & Mobile Navigation Drawer */}
                    <div className="flex items-center gap-1 sm:gap-2">
                        {/* 112 Hotline */}
                        <a
                            href="tel:112"
                            className={`inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold transition-colors sm:text-sm ${
                                isRedState
                                    ? 'bg-white text-red-700 hover:bg-gray-100'
                                    : 'bg-red-600 text-white hover:bg-red-700'
                            }`}
                        >
                            <Phone className="size-3.5 sm:size-4" />
                            <span>112</span>
                        </a>

                        {/* Desktop-only: Accessibility Button */}
                        <button
                            onClick={() => setIsA11yOpen(!isA11yOpen)}
                            className={buttonClass}
                            aria-label={t('a11y.open')}
                            title={t('a11y.open')}
                            aria-expanded={isA11yOpen}
                        >
                            <Eye className="size-4.5 sm:size-5" />
                        </button>

                        {/* Desktop-only: Search Button */}
                        <button
                            onClick={() => setIsSearchOpen(true)}
                            className={buttonClass}
                            aria-label={t('a11y.site_search')}
                        >
                            <Search className="size-4.5 sm:size-5" />
                        </button>

                        {/* Desktop-only: Language Switcher */}
                        <LanguageSwitcher className={switcherClass} />

                        {/* Mobile & Tablet Hamburger Drawer */}
                        <div className="hidden sm:block lg:hidden">
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
                                            className="size-5 sm:size-5.5"
                                            aria-hidden="true"
                                        />
                                    </button>
                                </SheetTrigger>
                                <SheetContent
                                    side="right"
                                    className="flex w-full max-w-xs flex-col gap-6 border-slate-800 bg-[#0f172a] p-6 text-white sm:max-w-sm"
                                >
                                    <SheetHeader className="flex justify-start border-b border-slate-800 pb-4 text-left">
                                        <SheetTitle className="flex items-center gap-2 text-lg font-bold text-white">
                                            <AppEmblem alt="" className="size-8 shrink-0" />
                                            <span className="truncate">
                                                {t('site.short_name')}
                                            </span>
                                        </SheetTitle>
                                    </SheetHeader>
                                    <nav
                                        aria-label={t('a11y.menu')}
                                        className="flex flex-1 flex-col gap-1 overflow-y-auto text-base font-medium"
                                    >
                                        <Link
                                            href={welcome({ locale }).url}
                                            onClick={() =>
                                                setIsMobileMenuOpen(false)
                                            }
                                            className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                                        >
                                            {t('nav.home')}
                                        </Link>
                                        <Link
                                            href={newsIndex({ locale }).url}
                                            onClick={() =>
                                                setIsMobileMenuOpen(false)
                                            }
                                            className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                                        >
                                            {t('nav.news')}
                                        </Link>
                                        <Link
                                            href={
                                                incidentsIndex({ locale }).url
                                            }
                                            onClick={() =>
                                                setIsMobileMenuOpen(false)
                                            }
                                            className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                                        >
                                            {t('nav.situation')}
                                        </Link>
                                        <Link
                                            href={mapIndex({ locale }).url}
                                            onClick={() =>
                                                setIsMobileMenuOpen(false)
                                            }
                                            className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                                        >
                                            {t('nav.map')}
                                        </Link>
                                        <Link
                                            href={
                                                documentsIndex({ locale }).url
                                            }
                                            onClick={() =>
                                                setIsMobileMenuOpen(false)
                                            }
                                            className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                                        >
                                            {t('nav.documents')}
                                        </Link>
                                        <Link
                                            href={appealsCreate({ locale }).url}
                                            onClick={() =>
                                                setIsMobileMenuOpen(false)
                                            }
                                            className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                                        >
                                            {t('nav.reception')}
                                        </Link>
                                        <Link
                                            href={
                                                touristGroupsCreate({ locale })
                                                    .url
                                            }
                                            onClick={() =>
                                                setIsMobileMenuOpen(false)
                                            }
                                            className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                                        >
                                            {t('nav.tourism')}
                                        </Link>
                                        <Link
                                            href={
                                                subscriptionsCreate({ locale })
                                                    .url
                                            }
                                            onClick={() =>
                                                setIsMobileMenuOpen(false)
                                            }
                                            className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                                        >
                                            {t('nav.subscribe')}
                                        </Link>

                                        <div className="my-4 border-t border-slate-800" />

                                        <span className="px-3 text-xs font-semibold tracking-wider text-slate-500 uppercase">
                                            {t('a11y.settings_search')}
                                        </span>

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
                                                <div className="my-4 border-t border-slate-800" />
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
                                                                            ? 'bg-blue-600 font-bold text-white shadow-sm'
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
                    {/* Agency identity */}
                    <div className="flex flex-col gap-3 sm:col-span-2 lg:col-span-1">
                        <div className="flex items-center gap-2.5">
                            <AppEmblem alt="" className="size-10 shrink-0" />
                            <span className="text-base font-bold">
                                {t('site.short_name')}
                            </span>
                        </div>
                        <p className="text-sm leading-relaxed text-brand-strong-foreground/70">
                            {t('site.full_name')}
                        </p>
                        <a
                            href="tel:112"
                            className="mt-1 inline-flex w-fit items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-sm font-semibold transition-colors hover:bg-white/15"
                        >
                            <Phone className="size-4" aria-hidden="true" />
                            {t('footer.hotline')}: 112
                        </a>
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
                            <Link
                                href={newsIndex({ locale }).url}
                                className="transition-colors hover:text-white"
                            >
                                {t('nav.news')}
                            </Link>
                            <Link
                                href={documentsIndex({ locale }).url}
                                className="transition-colors hover:text-white"
                            >
                                {t('nav.documents')}
                            </Link>
                            <Link
                                href={guidesIndex({ locale }).url}
                                className="transition-colors hover:text-white"
                            >
                                {t('nav.guides')}
                            </Link>
                            <Link
                                href={contactsIndex({ locale }).url}
                                className="transition-colors hover:text-white"
                            >
                                {t('nav.contacts')}
                            </Link>
                            {navPages.map((page) => (
                                <Link
                                    key={page.slug}
                                    href={
                                        pageShow({ locale, slug: page.slug }).url
                                    }
                                    className="transition-colors hover:text-white"
                                >
                                    {page.title}
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
                            © {new Date().getFullYear()} {t('site.short_name')} ·{' '}
                            {t('footer.rights')}
                        </span>
                        <div className="flex items-center gap-4">
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
