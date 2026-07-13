import { Head } from '@inertiajs/react';
import type { BlockData } from '@/components/admin/cp/blocks-field';
import { BlockRenderer } from '@/components/Public/block-renderer';
import { MissingTranslationAlert } from '@/components/Public/missing-translation-alert';
import { useTranslations } from '@/hooks/use-translations';

type StaticPage = {
    title: string;
    content: string | null;
    blocks?: BlockData[];
    updated_at: string | null;
    locale?: string;
};

type PageProps = {
    page: StaticPage;
};

export default function PageShow({ page }: PageProps) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={page.title} />

            <article className="mx-auto max-w-3xl">
                {page.locale && (
                    <MissingTranslationAlert contentLocale={page.locale} />
                )}

                <h1 className="text-3xl leading-tight font-semibold">
                    {page.title}
                </h1>

                {page.content && (
                    // Content is sanitised server-side (App\Support\HtmlSanitizer) before storage.
                    <div
                        className="rte-content mt-6 leading-relaxed"
                        dangerouslySetInnerHTML={{ __html: page.content }}
                    />
                )}

                {page.blocks && page.blocks.length > 0 && (
                    <div className="mt-8">
                        <BlockRenderer blocks={page.blocks} />
                    </div>
                )}

                {page.updated_at && (
                    <p className="mt-8 text-sm text-muted-foreground">
                        {t('common.updated', { date: page.updated_at })}
                    </p>
                )}
            </article>
        </>
    );
}
