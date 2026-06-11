import { Link, usePage } from '@inertiajs/react';
import { Check, Languages } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';

/**
 * Language switcher shown across the public portal (ТЗ §14). Reads the locale-aware shared props
 * and links to the same page in each active language; `localeSwitch` preserves the current path.
 */
export function LanguageSwitcher({ className }: { className?: string }) {
    const { locale, locales, localeSwitch } = usePage().props;
    const { t } = useTranslations();

    if (!locales || locales.length <= 1) {
        return null;
    }

    const current =
        locales.find((language) => language.code === locale) ?? locales[0];

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="sm"
                    className={cn('gap-2', className)}
                    aria-label={t('lang.switch')}
                >
                    <Languages className="size-4" />
                    <span className="uppercase">{current?.code}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {locales.map((language) => (
                    <DropdownMenuItem key={language.code} asChild>
                        <Link
                            href={localeSwitch[language.code] ?? '#'}
                            hrefLang={language.hreflang}
                            lang={language.hreflang}
                            className="flex cursor-pointer items-center justify-between gap-3"
                        >
                            <span
                                className={cn(
                                    language.code === locale && 'font-semibold',
                                )}
                            >
                                {language.native_name}
                            </span>
                            {language.code === locale && (
                                <Check className="size-4" />
                            )}
                        </Link>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
