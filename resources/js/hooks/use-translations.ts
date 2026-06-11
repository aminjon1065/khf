import { usePage } from '@inertiajs/react';
import type { Translations } from '@/types/locale';

/**
 * Resolve a dot-notation key (e.g. `nav.news`) against a nested dictionary, returning the key
 * itself when the path is missing so the UI degrades to a readable label instead of blank text.
 */
function resolve(dictionary: Translations, key: string): string {
    const value = key
        .split('.')
        .reduce<
            string | Translations | undefined
        >((node, segment) => (node && typeof node === 'object' ? node[segment] : undefined), dictionary);

    return typeof value === 'string' ? value : key;
}

/**
 * Interface-translation helper backed by the shared `translations` prop (ТЗ §14). The dictionary
 * already matches the active locale, so `t('nav.home')` returns the localized string and
 * `t('greeting', { name })` interpolates `:name`-style placeholders.
 */
export function useTranslations() {
    const { translations } = usePage().props;
    const dictionary = (translations ?? {}) as Translations;

    const t = (
        key: string,
        replacements: Record<string, string | number> = {},
    ): string => {
        let line = resolve(dictionary, key);

        for (const [token, value] of Object.entries(replacements)) {
            line = line.replace(`:${token}`, String(value));
        }

        return line;
    };

    return { t };
}
