import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronLeft, ExternalLink } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { index as servicesIndex } from '@/routes/services';

type Service = {
    title: string;
    summary: string | null;
    description: string | null;
    eligibility: string | null;
    required_documents: string | null;
    category_label: string;
    is_online: boolean;
    external_url: string | null;
    processing_time: string | null;
    fee: string | null;
};

export default function ServiceShow({ service }: { service: Service }) {
    const { locale } = usePage().props as { locale: string };
    const { t } = useTranslations();

    return (
        <>
            <Head title={service.title} />

            <article className="mx-auto max-w-3xl">
                <Link
                    href={servicesIndex({ locale }).url}
                    className="inline-flex items-center gap-1 text-sm text-primary hover:underline"
                >
                    <ChevronLeft className="size-4" />
                    {t('services.back')}
                </Link>

                <div className="mt-3 flex flex-wrap items-center gap-2">
                    <Badge variant="secondary">{service.category_label}</Badge>
                    {service.is_online && (
                        <Badge>{t('services.online')}</Badge>
                    )}
                </div>

                <h1 className="mt-2 text-3xl font-semibold">{service.title}</h1>

                {service.summary && (
                    <p className="mt-4 text-lg text-muted-foreground">
                        {service.summary}
                    </p>
                )}

                <dl className="mt-6 grid gap-3 rounded-lg border p-4 text-sm sm:grid-cols-2">
                    {service.processing_time && (
                        <div>
                            <dt className="text-muted-foreground">
                                {t('services.processing_time', {
                                    time: '',
                                }).replace(': ', '')}
                            </dt>
                            <dd className="font-medium">
                                {service.processing_time}
                            </dd>
                        </div>
                    )}
                    {service.fee && (
                        <div>
                            <dt className="text-muted-foreground">
                                {t('services.fee', { fee: '' }).replace(
                                    ': ',
                                    '',
                                )}
                            </dt>
                            <dd className="font-medium">{service.fee}</dd>
                        </div>
                    )}
                </dl>

                {service.is_online && service.external_url && (
                    <Button asChild className="mt-6">
                        <a
                            href={service.external_url}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {t('services.apply_online')}
                            <ExternalLink className="size-4" />
                        </a>
                    </Button>
                )}

                {service.description && (
                    <div
                        className="rte-content mt-8 leading-relaxed"
                        dangerouslySetInnerHTML={{
                            __html: service.description,
                        }}
                    />
                )}

                {service.eligibility && (
                    <section className="mt-8">
                        <h2 className="text-lg font-semibold">
                            {t('services.eligibility')}
                        </h2>
                        <div
                            className="rte-content mt-3 leading-relaxed text-muted-foreground"
                            dangerouslySetInnerHTML={{
                                __html: service.eligibility,
                            }}
                        />
                    </section>
                )}

                {service.required_documents && (
                    <section className="mt-8">
                        <h2 className="text-lg font-semibold">
                            {t('services.required_documents')}
                        </h2>
                        <div
                            className="rte-content mt-3 leading-relaxed text-muted-foreground"
                            dangerouslySetInnerHTML={{
                                __html: service.required_documents,
                            }}
                        />
                    </section>
                )}
            </article>
        </>
    );
}
