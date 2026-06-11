import { Head, Link, router, usePage } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { index as guidesIndex, show } from '@/routes/guides';

type GuideItem = {
    title: string | null;
    slug: string | null;
    summary: string | null;
    hazard_type: string | null;
    hazard_label: string | null;
    hazard_icon: string | null;
    audience: string;
    files_count: number;
};

type Option = { value: string; label: string };

type PageProps = {
    guides: GuideItem[];
    filters: { audience: string | null };
    audiences: Option[];
};

export default function GuidesIndex({ guides, filters, audiences }: PageProps) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

    const applyAudience = (audience: string | undefined) => {
        router.get(
            guidesIndex({ locale }).url,
            audience ? { audience } : {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <>
            <Head title={t('guides.title')} />

            <h1 className="mb-2 text-3xl font-semibold">{t('guides.title')}</h1>
            <p className="mb-6 text-muted-foreground">{t('guides.subtitle')}</p>

            <div className="mb-6 flex flex-wrap gap-2">
                <Button
                    type="button"
                    size="sm"
                    variant={filters.audience ? 'outline' : 'default'}
                    aria-pressed={!filters.audience}
                    onClick={() => applyAudience(undefined)}
                >
                    {t('guides.all_audiences')}
                </Button>
                {audiences.map((audience) => (
                    <Button
                        key={audience.value}
                        type="button"
                        size="sm"
                        variant={
                            filters.audience === audience.value
                                ? 'default'
                                : 'outline'
                        }
                        aria-pressed={filters.audience === audience.value}
                        onClick={() => applyAudience(audience.value)}
                    >
                        {audience.label}
                    </Button>
                ))}
            </div>

            {guides.length === 0 ? (
                <p className="text-muted-foreground">{t('guides.empty')}</p>
            ) : (
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {guides.map((guide) => (
                        <Link
                            key={guide.slug}
                            href={show({ locale, slug: guide.slug ?? '' }).url}
                            className="group flex flex-col gap-2 rounded-lg border p-4 transition-shadow hover:shadow-md"
                        >
                            {guide.hazard_label && (
                                <Badge variant="secondary" className="w-fit">
                                    {guide.hazard_label}
                                </Badge>
                            )}
                            <h2 className="leading-snug font-semibold group-hover:text-primary">
                                {guide.title}
                            </h2>
                            {guide.summary && (
                                <p className="line-clamp-2 text-sm text-muted-foreground">
                                    {guide.summary}
                                </p>
                            )}
                            {guide.files_count > 0 && (
                                <span className="mt-auto inline-flex items-center gap-1.5 text-sm text-muted-foreground">
                                    <Download className="size-4" />
                                    {guide.files_count}
                                </span>
                            )}
                        </Link>
                    ))}
                </div>
            )}
        </>
    );
}
