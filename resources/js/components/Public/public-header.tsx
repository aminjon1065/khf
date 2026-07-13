import { Link } from '@inertiajs/react';
import { Eye, Phone } from 'lucide-react';
import { AppEmblem } from '@/components/app-emblem';
import { PublicMobileNav } from '@/components/Public/public-mobile-nav';
import { PublicPrimaryNav } from '@/components/Public/public-primary-nav';
import { TajikistanEmblem } from '@/components/Public/symbols/state-emblem';
import { useTranslations } from '@/hooks/use-translations';
import { welcome } from '@/routes';
import type { NavEntry, NavLeaf, PublicLocale } from '@/types/public-layout';

type PublicHeaderProps = {
    locale: string;
    locales: PublicLocale[];
    localeSwitch: Record<string, string>;
    navEntries: NavEntry[];
    isActive: (href: string) => boolean;
    groupActive: (items: NavLeaf[]) => boolean;
    isRedState: boolean;
    headerClass: string;
    buttonClass: string;
    isA11yOpen: boolean;
    isMobileMenuOpen: boolean;
    onA11yToggle: () => void;
    onMobileMenuChange: (open: boolean) => void;
    onSearchOpen: () => void;
    onA11yOpen: () => void;
};

export function PublicHeader({
    locale,
    locales,
    localeSwitch,
    navEntries,
    isActive,
    groupActive,
    isRedState,
    headerClass,
    buttonClass,
    isA11yOpen,
    isMobileMenuOpen,
    onA11yToggle,
    onMobileMenuChange,
    onSearchOpen,
    onA11yOpen,
}: PublicHeaderProps) {
    const { t } = useTranslations();

    return (
        <header
            className={`sticky top-0 z-40 border-b transition-colors duration-300 print:hidden ${headerClass}`}
        >
            <div className="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3.5">
                <Link
                    href={welcome({ locale }).url}
                    className="flex shrink-0 items-center gap-3 transition-opacity hover:opacity-80"
                    aria-label={t('site.full_name')}
                >
                    <AppEmblem alt="" className="size-10 shrink-0 md:size-11" />
                    <TajikistanEmblem
                        alt=""
                        className="size-10 shrink-0 md:size-11"
                    />
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
                        onClick={onA11yToggle}
                        className={buttonClass}
                        aria-label={t('a11y.open')}
                        title={t('a11y.open')}
                        aria-expanded={isA11yOpen}
                    >
                        <Eye className="size-5" />
                    </button>

                    <PublicMobileNav
                        locale={locale}
                        locales={locales}
                        localeSwitch={localeSwitch}
                        navEntries={navEntries}
                        buttonClass={buttonClass}
                        open={isMobileMenuOpen}
                        onOpenChange={onMobileMenuChange}
                        onSearchOpen={onSearchOpen}
                        onA11yOpen={onA11yOpen}
                    />
                </div>
            </div>

            <PublicPrimaryNav
                navEntries={navEntries}
                isActive={isActive}
                groupActive={groupActive}
            />
        </header>
    );
}
