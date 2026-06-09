export interface AppLanguage {
    code: string;
    native_name: string;
    hreflang: string;
    is_default: boolean;
}

export interface ActiveAlert {
    id: number;
    level: string;
    level_label: string;
    color: string;
    title: string | null;
    body: string | null;
    dismissible: boolean;
}
