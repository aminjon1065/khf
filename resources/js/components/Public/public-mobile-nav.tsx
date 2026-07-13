import { Link } from '@inertiajs/react';
import { Eye, Menu, Search } from 'lucide-react';
import { AppEmblem } from '@/components/app-emblem';
import { TajikistanEmblem } from '@/components/Public/symbols/state-emblem';
import { ThemeToggle } from '@/components/theme-toggle';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useTranslations } from '@/hooks/use-translations';
import type { NavEntry, PublicLocale } from '@/types/public-layout';

type PublicMobileNavProps = {
    locale: string;
    locales: PublicLocale[];
    localeSwitch: Record<string, string>;
    navEntries: NavEntry[];
    buttonClass: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSearchOpen: () => void;
    onA11yOpen: () => void;
};

export function PublicMobileNav({
    locale,
    locales,
    localeSwitch,
    navEntries,
    buttonClass,
    open,
    onOpenChange,
    onSearchOpen,
    onA11yOpen,
}: PublicMobileNavProps) {
    const { t } = useTranslations();

    return (
        <div className="lg:hidden">
            <Sheet open={open} onOpenChange={onOpenChange}>
                <SheetTrigger asChild>
                    <button className={buttonClass} aria-label={t('a11y.menu')}>
                        <Menu className="size-5" aria-hidden="true" />
                    </button>
                </SheetTrigger>
                <SheetContent
                    side="right"
                    className="flex w-full max-w-xs flex-col gap-4 border-slate-800 bg-[#0b1220] p-6 text-white sm:max-w-sm"
                >
                    <SheetHeader className="flex justify-start border-b border-slate-800 pb-4 text-left">
                        <SheetTitle className="flex items-center gap-2 text-lg font-bold text-white">
                            <AppEmblem alt="" className="size-8 shrink-0" />
                            <TajikistanEmblem
                                alt=""
                                className="size-8 shrink-0"
                            />
                            <span className="sr-only">
                                {t('site.full_name')}
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
                                            onClick={() => onOpenChange(false)}
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
                                    onClick={() => onOpenChange(false)}
                                    className="rounded-md px-3 py-2 font-semibold text-slate-100 transition-colors hover:bg-slate-800 hover:text-white"
                                >
                                    {entry.label}
                                </Link>
                            ),
                        )}

                        <div className="my-3 border-t border-slate-800" />

                        <button
                            onClick={() => {
                                onOpenChange(false);
                                onSearchOpen();
                            }}
                            className="flex w-full cursor-pointer items-center gap-3 rounded-md px-3 py-2 text-left text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                        >
                            <Search className="size-4.5" />
                            <span>{t('a11y.site_search')}</span>
                        </button>

                        <button
                            onClick={() => {
                                onOpenChange(false);
                                onA11yOpen();
                            }}
                            className="flex w-full cursor-pointer items-center gap-3 rounded-md px-3 py-2 text-left text-slate-300 transition-colors hover:bg-slate-800 hover:text-white"
                        >
                            <Eye className="size-4.5" />
                            <span>{t('a11y.open')}</span>
                        </button>

                        <ThemeToggle
                            className="w-full justify-start gap-3 rounded-md px-3 py-2 text-slate-300 hover:bg-slate-800 hover:text-white"
                            iconClassName="size-4.5"
                            label={t('theme.toggle')}
                        />

                        {locales.length > 1 && (
                            <>
                                <div className="my-3 border-t border-slate-800" />
                                <div className="flex flex-col gap-2 px-3">
                                    <span className="text-xs font-semibold tracking-wider text-slate-500 uppercase">
                                        {t('lang.label')}
                                    </span>
                                    <div className="flex w-full rounded-md border border-slate-700 bg-slate-800 p-0.5">
                                        {locales.map((language) => (
                                            <Link
                                                key={language.code}
                                                href={
                                                    localeSwitch[
                                                        language.code
                                                    ] ?? '#'
                                                }
                                                hrefLang={language.hreflang}
                                                lang={language.hreflang}
                                                onClick={() =>
                                                    onOpenChange(false)
                                                }
                                                className={`flex-1 rounded py-1.5 text-center text-xs font-semibold transition-all ${
                                                    language.code === locale
                                                        ? 'bg-primary font-bold text-primary-foreground shadow-sm'
                                                        : 'text-slate-400 hover:text-white'
                                                }`}
                                            >
                                                {language.native_name}
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            </>
                        )}
                    </nav>
                </SheetContent>
            </Sheet>
        </div>
    );
}
