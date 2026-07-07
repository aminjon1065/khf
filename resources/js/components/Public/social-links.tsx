import { Facebook, Instagram, Send, Youtube } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';

export type SocialLink = {
    platform: string;
    url: string;
};

const icons: Record<string, LucideIcon> = {
    telegram: Send,
    facebook: Facebook,
    instagram: Instagram,
    youtube: Youtube,
};

function XIcon({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 24 24"
            aria-hidden="true"
            className={className}
            fill="currentColor"
        >
            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
        </svg>
    );
}

export function SocialLinks({ links }: { links: SocialLink[] }) {
    const { t } = useTranslations();

    if (links.length === 0) {
        return null;
    }

    return (
        <nav aria-label={t('footer.social.label')}>
            <p className="mb-2 text-xs font-semibold tracking-wider text-brand-strong-foreground/50 uppercase">
                {t('footer.social.heading')}
            </p>
            <ul className="flex flex-wrap gap-2">
                {links.map((link) => {
                    const Icon = icons[link.platform];

                    return (
                        <li key={link.platform}>
                            <a
                                href={link.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label={t(
                                    `footer.social.${link.platform}`,
                                )}
                                className="inline-flex size-9 items-center justify-center rounded-full border border-white/15 bg-white/5 text-brand-strong-foreground/80 transition-colors hover:border-white/30 hover:bg-white/10 hover:text-white focus-visible:ring-2 focus-visible:ring-white/40 focus-visible:outline-none"
                            >
                                {link.platform === 'x' ? (
                                    <XIcon className="size-4" />
                                ) : Icon ? (
                                    <Icon className="size-4" aria-hidden="true" />
                                ) : null}
                            </a>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
