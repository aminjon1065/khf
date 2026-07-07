import { usePage } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';

type Props = {
    contentLocale: string;
};

export function MissingTranslationAlert({ contentLocale }: Props) {
    const { props } = usePage();
    const { t } = useTranslations();
    const currentLocale = props.locale as string;

    if (contentLocale === currentLocale) {
        return null;
    }

    // Native language names are the same regardless of the current UI locale, so the banner reads
    // correctly in tj/ru/en without per-locale grammar handling.
    const languageNames: Record<string, string> = {
        tj: 'Тоҷикӣ',
        ru: 'Русский',
        en: 'English',
    };

    const contentLanguage = languageNames[contentLocale] || contentLocale;

    // Dictionary root is the `ui` array, so the key is `site.missing_translation` (no `ui.` prefix).
    const message = t('site.missing_translation', {
        language: contentLanguage,
    });

    return (
        <div className="mb-6 rounded-r-md border-l-4 border-amber-500 bg-amber-50 p-4">
            <div className="flex">
                <div className="flex-shrink-0">
                    <AlertTriangle
                        className="h-5 w-5 text-amber-400"
                        aria-hidden="true"
                    />
                </div>
                <div className="ml-3">
                    <p className="text-sm text-amber-700">{message}</p>
                </div>
            </div>
        </div>
    );
}
