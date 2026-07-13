import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronLeft, FileText, Printer } from 'lucide-react';
import { MissingTranslationAlert } from '@/components/Public/missing-translation-alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { index as guidesIndex } from '@/routes/guides';

type GuideFile = {
    name: string;
    size: string;
    url: string;
};

type Guide = {
    title: string;
    summary: string | null;
    content: string | null;
    hazard_label: string | null;
    audience_label: string;
    files: GuideFile[];
    is_missing_translation?: boolean;
    locale?: string;
};

type PageProps = {
    guide: Guide;
    seo: {
        title: string;
        description: string;
    };
};

export default function GuideShow({ guide }: PageProps) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

    return (
        <>
            <Head title={guide.title} />

            {guide.locale && (
                <MissingTranslationAlert contentLocale={guide.locale} />
            )}

            <article className="mx-auto max-w-3xl">
                <Link
                    href={guidesIndex({ locale: locale as string }).url}
                    className="inline-flex items-center gap-1 text-sm text-primary hover:underline print:hidden"
                >
                    <ChevronLeft className="size-4" />
                    {t('common.back')}
                </Link>

                <div className="mt-3 flex flex-wrap items-center gap-2">
                    {guide.hazard_label && (
                        <Badge variant="secondary">{guide.hazard_label}</Badge>
                    )}
                    <Badge variant="outline">{guide.audience_label}</Badge>
                </div>

                <h1 className="mt-2 text-3xl leading-tight font-semibold">
                    {guide.title}
                </h1>

                {guide.summary && (
                    <p className="mt-6 text-lg text-muted-foreground">
                        {guide.summary}
                    </p>
                )}

                {guide.content && (
                    // Content is sanitised server-side (App\Support\HtmlSanitizer) before storage.
                    <div
                        className="rte-content mt-6 leading-relaxed"
                        dangerouslySetInnerHTML={{ __html: guide.content }}
                    />
                )}

                {guide.files.length > 0 && (
                    <div className="mt-8">
                        <h2 className="text-lg font-semibold">
                            {t('guides.downloads')}
                        </h2>
                        <ul className="mt-3 space-y-1">
                            {guide.files.map((file) => (
                                <li key={file.url}>
                                    <a
                                        href={file.url}
                                        className="inline-flex items-center gap-2 text-sm text-primary hover:underline"
                                    >
                                        <FileText className="size-4" />
                                        {file.name}
                                        <span className="text-muted-foreground">
                                            ({file.size})
                                        </span>
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                <div className="mt-8 print:hidden">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => window.print()}
                    >
                        <Printer className="size-4" />
                        {t('guides.print')}
                    </Button>
                </div>
            </article>
        </>
    );
}
