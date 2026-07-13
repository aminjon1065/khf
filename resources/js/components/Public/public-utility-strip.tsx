import { Search } from 'lucide-react';
import { LanguageSwitcher } from '@/components/language-switcher';
import { TajikistanEmblem } from '@/components/Public/symbols/state-emblem';
import { TajikistanFlag } from '@/components/Public/symbols/state-flag';
import { ThemeToggle } from '@/components/theme-toggle';
import { useTranslations } from '@/hooks/use-translations';

type PublicUtilityStripProps = {
    onSearchOpen: () => void;
};

export function PublicUtilityStrip({ onSearchOpen }: PublicUtilityStripProps) {
    const { t } = useTranslations();

    return (
        <div className="border-b border-border bg-card text-muted-foreground print:hidden">
            <div className="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-1.5 text-xs">
                <div className="flex min-w-0 items-center gap-2.5">
                    <TajikistanEmblem className="size-5 shrink-0" />
                    <TajikistanFlag className="h-3.5 w-7 shrink-0 rounded-xs" />
                    <span className="truncate">{t('govbar.identifier')}</span>
                </div>
                <div className="flex shrink-0 items-center gap-1">
                    <ThemeToggle
                        className="px-2 py-1"
                        iconClassName="size-3.5"
                    />
                    <LanguageSwitcher className="text-muted-foreground hover:text-foreground" />
                    <span className="mx-1 hidden h-3 w-px bg-border sm:inline" />
                    <button
                        onClick={onSearchOpen}
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
    );
}
