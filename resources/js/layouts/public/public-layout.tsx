import { Link, usePage } from '@inertiajs/react';
import { AlertBanner } from '@/components/alert-banner';
import { LanguageSwitcher } from '@/components/language-switcher';
import { useTranslations } from '@/hooks/use-translations';
import { login, welcome } from '@/routes';
import { create as appealsCreate } from '@/routes/appeals';
import { index as documentsIndex } from '@/routes/documents';
import { index as incidentsIndex } from '@/routes/incidents';
import { index as mapIndex } from '@/routes/map';
import { index as newsIndex } from '@/routes/news';
import { create as subscriptionsCreate } from '@/routes/subscriptions';
import { create as touristGroupsCreate } from '@/routes/tourist-groups';

export default function PublicLayout({ children }: { children: React.ReactNode }) {
    const { locale, auth } = usePage().props;
    const { t } = useTranslations();

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <AlertBanner />
            <header className="bg-primary text-primary-foreground">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
                    <Link href={welcome({ locale }).url} className="text-lg font-semibold tracking-tight">
                        {t('site.short_name')}
                    </Link>
                    <nav className="flex items-center gap-1 text-sm sm:gap-3">
                        <Link
                            href={welcome({ locale }).url}
                            className="rounded px-2 py-1 hover:bg-primary-foreground/10"
                        >
                            {t('nav.home')}
                        </Link>
                        <Link
                            href={newsIndex({ locale }).url}
                            className="rounded px-2 py-1 hover:bg-primary-foreground/10"
                        >
                            {t('nav.news')}
                        </Link>
                        <Link
                            href={incidentsIndex({ locale }).url}
                            className="rounded px-2 py-1 hover:bg-primary-foreground/10"
                        >
                            {t('nav.situation')}
                        </Link>
                        <Link
                            href={mapIndex({ locale }).url}
                            className="rounded px-2 py-1 hover:bg-primary-foreground/10"
                        >
                            {t('nav.map')}
                        </Link>
                        <Link
                            href={documentsIndex({ locale }).url}
                            className="rounded px-2 py-1 hover:bg-primary-foreground/10"
                        >
                            {t('nav.documents')}
                        </Link>
                        <Link
                            href={appealsCreate({ locale }).url}
                            className="rounded px-2 py-1 hover:bg-primary-foreground/10"
                        >
                            {t('nav.reception')}
                        </Link>
                        <Link
                            href={touristGroupsCreate({ locale }).url}
                            className="hidden rounded px-2 py-1 hover:bg-primary-foreground/10 lg:inline"
                        >
                            {t('nav.tourism')}
                        </Link>
                        <Link
                            href={subscriptionsCreate({ locale }).url}
                            className="hidden rounded px-2 py-1 hover:bg-primary-foreground/10 lg:inline"
                        >
                            {t('nav.subscribe')}
                        </Link>
                        <LanguageSwitcher className="text-primary-foreground hover:bg-primary-foreground/10 hover:text-primary-foreground" />
                        {!auth.user && (
                            <Link
                                href={login().url}
                                className="rounded px-2 py-1 hover:bg-primary-foreground/10"
                            >
                                {t('nav.login')}
                            </Link>
                        )}
                    </nav>
                </div>
            </header>

            <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8">{children}</main>

            <footer className="border-t bg-muted">
                <div className="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-6 text-sm text-muted-foreground">
                    <p className="font-semibold text-foreground">{t('footer.hotline')}: 112</p>
                    <p>{t('site.full_name')}</p>
                </div>
            </footer>
        </div>
    );
}
