export type NavLeaf = { label: string; href: string };
export type NavEntry = NavLeaf | { label: string; items: NavLeaf[] };

export type PublicLocale = {
    code: string;
    native_name: string;
    hreflang: string;
};

export type PublicFooterContent = {
    government_url: string | null;
    egov_url: string | null;
    hotline: string;
    copyright: string | null;
    resource_links?: Array<{ label: string; url: string }>;
};

export type PublicMenuItem = {
    id?: number | string;
    title?: string | null;
    url?: string | null;
    children?: PublicMenuItem[];
};
