import type { Auth } from '@/types/auth';
import type { ActiveAlert, AppLanguage, Translations } from '@/types/locale';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            locale: string;
            locales: AppLanguage[];
            localeSwitch: Record<string, string>;
            translations: Translations;
            navPages: { title: string; slug: string }[];
            activeAlerts: ActiveAlert[];
            matomo?: { enabled: boolean; goals: Record<string, number> };
            president?: { url: string; photo: string };
            socialLinks?: Array<{ platform: string; url: string }>;
            footerContent?: {
                government_url: string | null;
                egov_url: string | null;
                hotline: string;
                copyright: string | null;
            };
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}

declare global {
    interface Window {
        _paq?: any[];
    }
}
