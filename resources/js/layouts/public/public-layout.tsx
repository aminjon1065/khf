import { Link, usePage } from '@inertiajs/react';
import { AlertBanner } from '@/components/alert-banner';
import { LanguageSwitcher } from '@/components/language-switcher';
import { login, welcome } from '@/routes';
import { index as incidentsIndex } from '@/routes/incidents';
import { index as newsIndex } from '@/routes/news';

export default function PublicLayout({ children }: { children: React.ReactNode }) {
    const { locale, auth } = usePage().props;

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <AlertBanner />
            <header className="bg-primary text-primary-foreground">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
                    <Link href={welcome({ locale }).url} className="text-lg font-semibold tracking-tight">
                        КЧС
                    </Link>
                    <nav className="flex items-center gap-1 text-sm sm:gap-3">
                        <Link
                            href={welcome({ locale }).url}
                            className="rounded px-2 py-1 hover:bg-primary-foreground/10"
                        >
                            Главная
                        </Link>
                        <Link
                            href={newsIndex({ locale }).url}
                            className="rounded px-2 py-1 hover:bg-primary-foreground/10"
                        >
                            Новости
                        </Link>
                        <Link
                            href={incidentsIndex({ locale }).url}
                            className="rounded px-2 py-1 hover:bg-primary-foreground/10"
                        >
                            Обстановка
                        </Link>
                        <LanguageSwitcher className="text-primary-foreground hover:bg-primary-foreground/10 hover:text-primary-foreground" />
                        {!auth.user && (
                            <Link
                                href={login().url}
                                className="rounded px-2 py-1 hover:bg-primary-foreground/10"
                            >
                                Войти
                            </Link>
                        )}
                    </nav>
                </div>
            </header>

            <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8">{children}</main>

            <footer className="border-t bg-muted">
                <div className="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-6 text-sm text-muted-foreground">
                    <p className="font-semibold text-foreground">Единый телефон доверия: 112</p>
                    <p>Комитет по чрезвычайным ситуациям и гражданской обороне при Правительстве Республики Таджикистан</p>
                </div>
            </footer>
        </div>
    );
}
