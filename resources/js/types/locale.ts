export interface AppLanguage {
    code: string;
    native_name: string;
    hreflang: string;
    is_default: boolean;
}

/**
 * Nested interface dictionary for the active locale (mirrors lang/{locale}/ui.php). Looked up by
 * dot-notation key via the `useTranslations` hook.
 */
export interface Translations {
    [key: string]: string | Translations;
}

export interface ActiveAlert {
    id: number;
    level: string;
    level_label: string;
    color: string;
    title: string | null;
    body: string | null;
    dismissible: boolean;
    published_at: string | null;
    expires_at: string | null;
    url: string;
}
