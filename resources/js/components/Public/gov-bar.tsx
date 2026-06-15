import { Lock, ShieldCheck } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';

/**
 * Official government identifier bar — the primary govtech trust signal (cf. GOV.UK / USWDS /
 * France DSFR). A slim strip pinned above the masthead declaring this is an official state portal
 * of the Republic of Tajikistan. Tokenised on the КЧС deep-navy brand chrome.
 */
export function GovBar() {
    const { t } = useTranslations();

    return (
        <div className="bg-brand-strong text-brand-strong-foreground print:hidden">
            <div className="mx-auto flex max-w-6xl items-center gap-2 px-4 py-1.5 text-xs">
                <ShieldCheck
                    className="size-3.5 shrink-0 text-signal"
                    aria-hidden="true"
                />
                <span className="truncate text-brand-strong-foreground/85">
                    {t('govbar.identifier')}
                </span>
                <span className="ml-auto hidden shrink-0 items-center gap-1.5 text-brand-strong-foreground/60 sm:inline-flex">
                    <Lock className="size-3" aria-hidden="true" />
                    {t('govbar.secure')}
                </span>
            </div>
        </div>
    );
}
