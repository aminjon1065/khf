import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Eye, Phone, Search } from 'lucide-react';
import { AlertBanner } from '@/components/alert-banner';
import { AppEmblem } from '@/components/app-emblem';
import { LanguageSwitcher } from '@/components/language-switcher';
import { GlobalSearchModal } from '@/components/Public/GlobalSearchModal';
import { AccessibilityToolbar } from '@/components/accessibility-toolbar';
import { AdminBar } from '@/components/admin-bar';
import { useTranslations } from '@/hooks/use-translations';
import { login, welcome } from '@/routes';
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
    
    // Check for critical alerts to trigger Red State header
    const activeAlerts = (props.activeAlerts as Array<{ level: string }>) ?? [];
    const isRedState = activeAlerts.some((a) => a.level === 'critical');

    // Admin privileges check for WordPress-style AdminBar
    const canManage = auth?.user && (
        auth.roles?.includes('super-admin') ||
        auth.roles?.includes('moderator') ||
        auth.permissions?.includes('posts.manage') ||
        auth.permissions?.includes('pages.manage')
    );

    const pageId = (props.page as { id?: number })?.id;
    const postId = (props.post as { id?: number })?.id;

    return (
        <div className="flex min-h-screen flex-col bg-card text-foreground font-sans antialiased selection:bg-primary/20">
            {canManage && (
                <AdminBar pageId={pageId} postId={postId} />
            )}
            {isA11yOpen && (
                <AccessibilityToolbar onClose={() => setIsA11yOpen(false)} />
            )}
            <AlertBanner />
            <header className={`sticky top-0 z-50 border-b print:hidden transition-all duration-500 ${isRedState ? 'bg-red-900 text-white border-red-700 shadow-lg' : 'bg-[#0f172a] text-white border-slate-800 shadow-md'}`}>
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
                    <Link
                        href={welcome({ locale }).url}
                        className="flex items-center gap-3 transition-opacity hover:opacity-80"
                    >
                        <AppEmblem className="size-10 shrink-0" />
                        <span className="text-xl font-bold tracking-tight">
                            {t('site.short_name')}
                        </span>
                    </Link>
                    <nav className="flex items-center gap-1 text-sm font-medium sm:gap-2">
                        <Link
                            href={welcome({ locale }).url}
                            className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                        >
                            {t('nav.home')}
                        </Link>
                        <Link
                            href={newsIndex({ locale }).url}
                            className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                        >
                            {t('nav.news')}
                        </Link>
                        <Link
                            href={incidentsIndex({ locale }).url}
                            className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                        >
                            {t('nav.situation')}
                        </Link>
                        <Link
                            href={mapIndex({ locale }).url}
                            className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                        >
                            {t('nav.map')}
                        </Link>
                        <Link
                            href={documentsIndex({ locale }).url}
                            className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                        >
                            {t('nav.documents')}
                        </Link>
                        <Link
                            href={appealsCreate({ locale }).url}
                            className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                        >
                            {t('nav.reception')}
                        </Link>
                        <Link
                            href={touristGroupsCreate({ locale }).url}
                            className="hidden rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white lg:inline"
                        >
                            {t('nav.tourism')}
                        </Link>
                        <Link
                            href={subscriptionsCreate({ locale }).url}
                            className="hidden rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white lg:inline"
                        >
                            {t('nav.subscribe')}
                        </Link>
                        <div className="ml-2 border-l border-white/20 pl-2 flex items-center gap-2">
                            <a
                                href="tel:112"
                                className={`hidden sm:inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-bold transition-colors ${isRedState ? 'bg-white text-red-700 hover:bg-gray-100' : 'bg-red-600 text-white hover:bg-red-700'}`}
                            >
                                <Phone className="size-4" />
                                112
                            </a>
                            <button
                                onClick={() => setIsA11yOpen(!isA11yOpen)}
                                className="p-2 rounded-md text-slate-300 hover:bg-slate-800 hover:text-white transition-colors cursor-pointer"
                                aria-label="Версия для слабовидящих"
                                title="Версия для слабовидящих"
                            >
                                <Eye className="size-5" />
                            </button>
                            <button
                                onClick={() => setIsSearchOpen(true)}
                                className="p-2 rounded-md text-slate-300 hover:bg-slate-800 hover:text-white transition-colors cursor-pointer"
                                aria-label="Search"
                            >
                                <Search className="size-5" />
                            </button>
                            <LanguageSwitcher className="text-slate-300 hover:bg-slate-800 hover:text-white" />
                            {!auth?.user && (
                                <Link
                                    href={login().url}
                                    className="rounded-md px-3 py-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                                >
                                    {t('nav.login')}
                                </Link>
                            )}
                        </div>
                    </nav>
                </div>
            </header>

            <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-10 sm:py-16">
                {children}
            </main>

            <footer className="border-t bg-muted print:hidden">
                <div className="mx-auto grid max-w-6xl gap-8 px-4 py-10 text-sm text-muted-foreground sm:grid-cols-3">
                    <div className="flex flex-col gap-2">
                        <p className="font-semibold text-foreground">
                            {t('footer.hotline')}: 112
                        </p>
                        <p className="leading-relaxed">{t('site.full_name')}</p>
                    </div>
                    
                    <div className="flex flex-col gap-2">
                        <p className="font-semibold text-foreground">
                            Полезные ресурсы
                        </p>
                        <ul className="space-y-2">
                            <li>
                                <a href="https://president.tj" target="_blank" rel="noopener noreferrer" className="hover:text-primary transition-colors">
                                    Президент Республики Таджикистан
                                </a>
                            </li>
                            <li>
                                <a href="https://government.tj" target="_blank" rel="noopener noreferrer" className="hover:text-primary transition-colors">
                                    Правительство Республики Таджикистан
                                </a>
                            </li>
                            <li>
                                <a href="https://egov.tj" target="_blank" rel="noopener noreferrer" className="hover:text-primary transition-colors">
                                    Портал государственных услуг РТ
                                </a>
                            </li>
                        </ul>
                    </div>

                    <nav className="flex flex-col gap-2">
                        <p className="font-semibold text-foreground">
                            {t('footer.sections')}
                        </p>
                        <div className="flex flex-col gap-2">
                            <Link href={guidesIndex({ locale }).url} className="hover:text-primary transition-colors">
                                {t('nav.guides')}
                            </Link>
                            <Link href={contactsIndex({ locale }).url} className="hover:text-primary transition-colors">
                                {t('nav.contacts')}
                            </Link>
                            {navPages.map((page) => (
                                <Link
                                    key={page.slug}
                                    href={pageShow({ locale, slug: page.slug }).url}
                                    className="hover:text-primary transition-colors"
                                >
                                    {page.title}
                                </Link>
                            ))}
                        </div>
                    </nav>
                </div>
            </footer>

            <GlobalSearchModal isOpen={isSearchOpen} setIsOpen={setIsSearchOpen} />
        </div>
    );
}
