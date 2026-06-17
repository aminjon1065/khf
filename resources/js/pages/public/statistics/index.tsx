import { Head } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';

type Stat = {
    id: number;
    value: string;
    unit: string | null;
    label: string | null;
    year: number | null;
};

export default function StatisticsIndex({
    statistics,
}: {
    statistics: Stat[];
}) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('statistics.title')} />

            <h1 className="text-3xl font-semibold">{t('statistics.title')}</h1>
            <p className="mt-1 text-muted-foreground">
                {t('statistics.subtitle')}
            </p>

            {statistics.length === 0 ? (
                <p className="mt-8 text-muted-foreground">
                    {t('statistics.empty')}
                </p>
            ) : (
                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {statistics.map((stat) => (
                        <div
                            key={stat.id}
                            className="rounded-lg border bg-card p-6 text-center"
                        >
                            <div className="text-4xl font-bold text-primary">
                                {stat.value}
                                {stat.unit && (
                                    <span className="ml-1 text-xl font-semibold text-primary/80">
                                        {stat.unit}
                                    </span>
                                )}
                            </div>
                            <div className="mt-2 text-sm font-medium">
                                {stat.label}
                            </div>
                            {stat.year && (
                                <div className="mt-1 text-xs text-muted-foreground">
                                    {t('statistics.year', { year: stat.year })}
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </>
    );
}
