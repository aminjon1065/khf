import { AlertTriangle } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';
import { usePage } from '@inertiajs/react';

type Props = {
    contentLocale: string;
};

export function MissingTranslationAlert({ contentLocale }: Props) {
    const { props } = usePage();
    const currentLocale = props.locale as string;

    if (contentLocale === currentLocale) {
        return null;
    }

    const { t } = useTranslations();

    const languageNames: Record<string, string> = {
        tj: 'тоҷикӣ',
        ru: 'русском',
        en: 'English',
    };

    const contentLanguage = languageNames[contentLocale] || contentLocale;

    // Use translations if available, otherwise fallback to ru string since ru is typically understood if missing translation.
    const message = t('ui.site.missing_translation', { language: contentLanguage }) || 
                    `Эта страница недоступна на вашем языке. Показана версия на ${contentLanguage} языке.`;

    return (
        <div className="bg-amber-50 border-l-4 border-amber-500 p-4 mb-6 rounded-r-md">
            <div className="flex">
                <div className="flex-shrink-0">
                    <AlertTriangle className="h-5 w-5 text-amber-400" aria-hidden="true" />
                </div>
                <div className="ml-3">
                    <p className="text-sm text-amber-700">
                        {message}
                    </p>
                </div>
            </div>
        </div>
    );
}
