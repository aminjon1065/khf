import { Link } from '@inertiajs/react';
import { ExternalLink, Eye, Phone } from 'lucide-react';
import { AppEmblem } from '@/components/app-emblem';
import { SocialLinks } from '@/components/Public/social-links';
import { TajikistanEmblem } from '@/components/Public/symbols/state-emblem';
import { useTranslations } from '@/hooks/use-translations';
import { show as pageShow } from '@/routes/pages';
import type {
    PublicFooterContent,
    PublicMenuItem,
} from '@/types/public-layout';

type PublicFooterProps = {
    locale: string;
    hotline: string;
    socialLinks: Array<{ platform: string; url: string }>;
    rawFooter: PublicMenuItem[];
    footerContent: PublicFooterContent;
    president?: { url: string; photo: string };
    onA11yOpen: () => void;
};

export function PublicFooter({
    locale,
    hotline,
    socialLinks,
    rawFooter,
    footerContent,
    president,
    onA11yOpen,
}: PublicFooterProps) {
    const { t } = useTranslations();

    return (
        <footer className="bg-brand-strong text-brand-strong-foreground print:hidden">
            <div className="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
                <div className="flex flex-col gap-5 sm:col-span-2 lg:col-span-1">
                    <div className="flex flex-col gap-3">
                        <div className="flex items-center gap-2.5">
                            <AppEmblem alt="" className="size-10 shrink-0" />
                            <TajikistanEmblem
                                alt=""
                                className="size-10 shrink-0"
                            />
                        </div>
                        <p className="text-sm leading-relaxed text-brand-strong-foreground/70">
                            {t('site.full_name')}
                        </p>
                        <a
                            href={`tel:${hotline}`}
                            className="inline-flex w-fit items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-sm font-semibold transition-colors hover:bg-white/15"
                        >
                            <Phone className="size-4" aria-hidden="true" />
                            {t('footer.hotline')}: {hotline}
                        </a>
                        <SocialLinks links={socialLinks} />
                    </div>

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

                <nav
                    aria-label={t('a11y.footer_nav')}
                    className="flex flex-col gap-3"
                >
                    <p className="text-xs font-semibold tracking-wider text-brand-strong-foreground/50 uppercase">
                        {t('footer.sections')}
                    </p>
                    <div className="flex flex-col gap-2 text-sm text-brand-strong-foreground/80">
                        {rawFooter
                            .filter((item) => item.title)
                            .map((item) => (
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

                <div className="flex flex-col gap-3">
                    <p className="text-xs font-semibold tracking-wider text-brand-strong-foreground/50 uppercase">
                        {t('contacts.emergency_numbers')}
                    </p>
                    <ul className="flex flex-col gap-2 text-sm text-brand-strong-foreground/80">
                        <li className="flex items-center justify-between gap-3">
                            <span>{t('contacts.helpline')}</span>
                            <a
                                href={`tel:${hotline}`}
                                className="font-bold tabular-nums hover:text-white"
                            >
                                {hotline}
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

                <div className="flex flex-col gap-3">
                    <p className="text-xs font-semibold tracking-wider text-brand-strong-foreground/50 uppercase">
                        {t('footer.useful_resources')}
                    </p>
                    <ul className="flex flex-col gap-2 text-sm text-brand-strong-foreground/80">
                        {president?.url ? (
                            <li>
                                <a
                                    href={president.url}
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
                        ) : null}
                        {footerContent.government_url ? (
                            <li>
                                <a
                                    href={footerContent.government_url}
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
                        ) : null}
                        {footerContent.egov_url ? (
                            <li>
                                <a
                                    href={footerContent.egov_url}
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
                        ) : null}
                        {(footerContent.resource_links ?? []).map((link) => (
                            <li key={link.url}>
                                <a
                                    href={link.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1.5 transition-colors hover:text-white"
                                >
                                    {link.label}
                                    <ExternalLink
                                        className="size-3 opacity-50"
                                        aria-hidden="true"
                                    />
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>

            <div className="border-t border-white/10">
                <div className="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-4 text-xs text-brand-strong-foreground/60 sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        © {new Date().getFullYear()} {t('site.short_name')} ·{' '}
                        {t('footer.rights')}
                        {footerContent.copyright
                            ? ` · ${footerContent.copyright}`
                            : ''}
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
                            onClick={onA11yOpen}
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
    );
}
