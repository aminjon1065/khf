import { Head } from '@inertiajs/react';

type StaticPage = {
    title: string;
    content: string | null;
    updated_at: string | null;
};

type PageProps = {
    page: StaticPage;
};

export default function PageShow({ page }: PageProps) {
    return (
        <>
            <Head title={page.title} />

            <article className="mx-auto max-w-3xl">
                <h1 className="text-3xl font-semibold leading-tight">{page.title}</h1>

                {page.content && (
                    // Content is sanitised server-side (App\Support\HtmlSanitizer) before storage.
                    <div
                        className="rte-content mt-6 leading-relaxed"
                        dangerouslySetInnerHTML={{ __html: page.content }}
                    />
                )}
            </article>
        </>
    );
}
