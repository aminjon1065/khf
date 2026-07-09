import { ExternalLink } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type LocaleOption = { code: string; native_name: string };

/**
 * Opens the published public URL in a new tab. When several locales are available, offers a picker.
 */
export function CpViewOnSite({
    urls,
    locales,
    defaultLocale,
}: {
    urls: Record<string, string>;
    locales: LocaleOption[];
    defaultLocale: string;
}) {
    const entries = locales
        .map((locale) => ({ locale, url: urls[locale.code] }))
        .filter((entry): entry is { locale: LocaleOption; url: string } => !!entry.url);

    if (entries.length === 0) {
        return null;
    }

    if (entries.length === 1) {
        return (
            <Button type="button" variant="outline" size="sm" asChild>
                <a href={entries[0].url} target="_blank" rel="noopener noreferrer">
                    <ExternalLink className="size-4" />
                    На сайте
                </a>
            </Button>
        );
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button type="button" variant="outline" size="sm">
                    <ExternalLink className="size-4" />
                    На сайте
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {entries.map(({ locale, url }) => (
                    <DropdownMenuItem key={locale.code} asChild>
                        <a href={url} target="_blank" rel="noopener noreferrer">
                            {locale.native_name}
                            {locale.code === defaultLocale ? ' · основной' : ''}
                        </a>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
