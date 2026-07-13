import { Head, Link, router, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { index as servicesIndex, show } from '@/routes/services';

type ServiceItem = {
    title: string | null;
    slug: string | null;
    summary: string | null;
    category: string;
    category_label: string;
    is_online: boolean;
    processing_time: string | null;
    fee: string | null;
};

type Option = { value: string; label: string };

type PageProps = {
    services: ServiceItem[];
    filters: { category: string | null };
    categories: Option[];
};

export default function ServicesIndex({
    services,
    filters,
    categories,
}: PageProps) {
    const { locale } = usePage().props as { locale: string };
    const { t } = useTranslations();

    const applyCategory = (category: string | undefined) => {
        router.get(
            servicesIndex({ locale }).url,
            category ? { category } : {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <>
            <Head title={t('services.title')} />

            <h1 className="mb-2 text-3xl font-semibold">
                {t('services.title')}
            </h1>
            <p className="mb-6 text-muted-foreground">
                {t('services.subtitle')}
            </p>

            <div className="mb-6 flex flex-wrap gap-2">
                <Button
                    type="button"
                    size="sm"
                    variant={filters.category ? 'outline' : 'default'}
                    aria-pressed={!filters.category}
                    onClick={() => applyCategory(undefined)}
                >
                    {t('services.all_categories')}
                </Button>
                {categories.map((category) => (
                    <Button
                        key={category.value}
                        type="button"
                        size="sm"
                        variant={
                            filters.category === category.value
                                ? 'default'
                                : 'outline'
                        }
                        aria-pressed={filters.category === category.value}
                        onClick={() => applyCategory(category.value)}
                    >
                        {category.label}
                    </Button>
                ))}
            </div>

            {services.length === 0 ? (
                <p className="text-muted-foreground">{t('services.empty')}</p>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2">
                    {services.map((service) => (
                        <Link
                            key={service.slug}
                            href={
                                service.slug
                                    ? show({ locale, slug: service.slug }).url
                                    : '#'
                            }
                            className="block rounded-lg border p-5 transition-colors hover:border-primary/40 hover:bg-muted/30"
                        >
                            <div className="flex flex-wrap items-center gap-2">
                                <h2 className="font-medium">{service.title}</h2>
                                {service.is_online && (
                                    <Badge>{t('services.online')}</Badge>
                                )}
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {service.category_label}
                            </p>
                            {service.summary && (
                                <p className="mt-2 line-clamp-2 text-sm text-muted-foreground">
                                    {service.summary}
                                </p>
                            )}
                            <div className="mt-3 flex flex-wrap gap-3 text-xs text-muted-foreground">
                                {service.processing_time && (
                                    <span>
                                        {t('services.processing_time', {
                                            time: service.processing_time,
                                        })}
                                    </span>
                                )}
                                {service.fee && (
                                    <span>
                                        {t('services.fee', {
                                            fee: service.fee,
                                        })}
                                    </span>
                                )}
                            </div>
                        </Link>
                    ))}
                </div>
            )}
        </>
    );
}
