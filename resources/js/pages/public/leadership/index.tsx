import { Head } from '@inertiajs/react';
import { CalendarClock, Mail, Phone } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';

type Leader = {
    id: number;
    full_name: string | null;
    position: string | null;
    bio: string | null;
    reception: string | null;
    email: string | null;
    phone: string | null;
    photo_url: string | null;
};

export default function LeadershipIndex({ leaders }: { leaders: Leader[] }) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('leadership.title')} />

            <h1 className="text-3xl font-semibold">{t('leadership.title')}</h1>
            <p className="mt-1 text-muted-foreground">
                {t('leadership.subtitle')}
            </p>

            {leaders.length === 0 ? (
                <p className="mt-8 text-muted-foreground">
                    {t('leadership.empty')}
                </p>
            ) : (
                <div className="mt-6 space-y-6">
                    {leaders.map((leader) => (
                        <div
                            key={leader.id}
                            className="flex flex-col gap-5 rounded-lg border p-5 sm:flex-row"
                        >
                            <div className="shrink-0">
                                {leader.photo_url ? (
                                    <img
                                        src={leader.photo_url}
                                        alt={leader.full_name ?? ''}
                                        className="size-32 rounded-lg object-cover"
                                    />
                                ) : (
                                    <div className="size-32 rounded-lg bg-muted" />
                                )}
                            </div>
                            <div className="min-w-0 flex-1">
                                <h2 className="text-xl font-semibold">
                                    {leader.full_name}
                                </h2>
                                <p className="font-medium text-primary">
                                    {leader.position}
                                </p>

                                {leader.bio && (
                                    <div
                                        className="rte-content mt-3 text-sm leading-relaxed text-muted-foreground"
                                        dangerouslySetInnerHTML={{
                                            __html: leader.bio,
                                        }}
                                    />
                                )}

                                {leader.reception && (
                                    <p className="mt-3 flex items-center gap-2 text-sm">
                                        <CalendarClock className="size-4 text-primary" />
                                        <span className="font-medium">
                                            {t('leadership.reception')}:
                                        </span>{' '}
                                        {leader.reception}
                                    </p>
                                )}

                                {(leader.email || leader.phone) && (
                                    <div className="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm text-muted-foreground">
                                        {leader.email && (
                                            <a
                                                href={`mailto:${leader.email}`}
                                                className="inline-flex items-center gap-1.5 hover:text-primary"
                                            >
                                                <Mail className="size-4" />
                                                {leader.email}
                                            </a>
                                        )}
                                        {leader.phone && (
                                            <a
                                                href={`tel:${leader.phone}`}
                                                className="inline-flex items-center gap-1.5 hover:text-primary"
                                            >
                                                <Phone className="size-4" />
                                                {leader.phone}
                                            </a>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </>
    );
}
