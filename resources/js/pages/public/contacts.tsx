import { Head, Link, usePage } from '@inertiajs/react';
import { Ambulance, Flame, MapPin, Phone, Shield } from 'lucide-react';
import { useMemo } from 'react';
import { MapView } from '@/components/map-view';
import type { MapMarker } from '@/components/map-view';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { create as appealsCreate } from '@/routes/appeals';

type Region = {
    id: number;
    name: string;
    lat: number;
    lng: number;
};

type PageProps = {
    regions: Region[];
};

export default function PublicContacts({ regions }: PageProps) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

    const emergencyNumbers = [
        {
            icon: Phone,
            label: t('contacts.helpline'),
            number: '112',
            primary: true,
        },
        {
            icon: Flame,
            label: t('contacts.fire'),
            number: '101',
            primary: false,
        },
        {
            icon: Shield,
            label: t('contacts.police'),
            number: '102',
            primary: false,
        },
        {
            icon: Ambulance,
            label: t('contacts.ambulance'),
            number: '103',
            primary: false,
        },
    ];

    const markers = useMemo<MapMarker[]>(
        () =>
            regions.map((region) => ({
                id: region.id,
                lat: region.lat,
                lng: region.lng,
                color: '#1f4e8c',
                title: region.name,
            })),
        [regions],
    );

    return (
        <>
            <Head title={t('contacts.title')} />

            <div className="mb-8">
                <h1 className="text-3xl font-semibold">
                    {t('contacts.title')}
                </h1>
                <p className="text-muted-foreground">
                    {t('contacts.subtitle')}
                </p>
            </div>

            <section className="mb-10">
                <h2 className="mb-4 text-xl font-semibold">
                    {t('contacts.emergency_numbers')}
                </h2>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {emergencyNumbers.map((item) => (
                        <a
                            key={item.number}
                            href={`tel:${item.number}`}
                            className={
                                item.primary
                                    ? 'flex items-center gap-4 rounded-lg border border-primary bg-primary/5 p-5 transition-colors hover:bg-primary/10'
                                    : 'flex items-center gap-4 rounded-lg border p-5 transition-colors hover:bg-accent'
                            }
                        >
                            <item.icon
                                className={
                                    item.primary
                                        ? 'size-7 shrink-0 text-primary'
                                        : 'size-7 shrink-0 text-muted-foreground'
                                }
                            />
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    {item.label}
                                </p>
                                <p
                                    className={
                                        item.primary
                                            ? 'text-3xl font-semibold text-primary'
                                            : 'text-3xl font-semibold'
                                    }
                                >
                                    {item.number}
                                </p>
                            </div>
                        </a>
                    ))}
                </div>
            </section>

            <section className="mb-10">
                <h2 className="mb-4 text-xl font-semibold">
                    {t('contacts.regional_offices')}
                </h2>
                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <MapView
                            markers={markers}
                            className="h-96 w-full rounded-lg border"
                        />
                    </div>
                    <ul className="space-y-2">
                        {regions.map((region) => (
                            <li
                                key={region.id}
                                className="flex items-center gap-2 rounded-lg border p-3"
                            >
                                <MapPin className="size-4 shrink-0 text-primary" />
                                <span className="text-sm font-medium">
                                    {region.name}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            <section className="mb-4">
                <h2 className="mb-4 text-xl font-semibold">
                    {t('contacts.feedback')}
                </h2>
                <div className="flex flex-col items-start gap-4 rounded-lg border p-6 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-muted-foreground">
                        {t('contacts.feedback_text')}
                    </p>
                    <Button asChild>
                        <Link href={appealsCreate({ locale }).url}>
                            {t('contacts.feedback_cta')}
                        </Link>
                    </Button>
                </div>
            </section>
        </>
    );
}
