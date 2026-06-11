import { Head } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';

type StaticPage = {
    title: string;
    content: string | null;
    updated_at: string | null;
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

                {page.updated_at && (
                    <p className="mt-8 text-sm text-muted-foreground">
                        {t('common.updated', { date: page.updated_at })}
                    </p>
                )}
            </article>
        </>
    );
}
