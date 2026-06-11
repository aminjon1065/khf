import { Link, usePage } from '@inertiajs/react';
import { AlertTriangle, Map, Phone, ShieldAlert } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';

export type ActiveAlert = {
    id: number;
    level: string;
    level_label: string;
    color: string;
    title: string | null;
    body: string | null;
    dismissible: boolean;
};

export function EmergencyHero({ alerts }: { alerts: ActiveAlert[] }) {
    const { t } = useTranslations();
    const { locale } = usePage().props;

    // Use the highest severity alert to drive the banner
    const primaryAlert = alerts[0];

    return (
        <section className="relative overflow-hidden rounded-2xl bg-gradient-to-b from-red-600 to-red-800 px-6 py-12 text-center sm:px-10 sm:py-20 border-2 border-red-500 shadow-2xl animate-in fade-in duration-500">
            <div className="absolute inset-0 bg-black/10 mix-blend-overlay" />
            <div className="relative mx-auto flex max-w-4xl flex-col items-center z-10">
                <div className="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-white text-red-600 shadow-lg animate-pulse">
                    <AlertTriangle className="size-10" strokeWidth={2.5} />
                </div>
                
                <h1 className="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl uppercase">
                    {primaryAlert?.title || t('home.emergency.title')}
                </h1>
                
                {primaryAlert?.body && (
                    <p className="mt-6 max-w-3xl text-lg leading-relaxed text-red-100 md:text-xl font-medium">
                        {primaryAlert.body}
                    </p>
                )}

                <div className="mt-12 grid gap-4 w-full max-w-2xl sm:grid-cols-2">
                    <Link
                        href={`/${locale as string}/guides`}
                        className="group flex flex-col items-center justify-center gap-2 rounded-xl bg-white/10 px-6 py-6 text-white backdrop-blur-sm transition-all hover:bg-white/20 hover:shadow-lg border border-white/20"
                    >
                        <ShieldAlert className="size-8 mb-2 opacity-90 group-hover:scale-110 transition-transform" />
                        <span className="text-lg font-bold uppercase tracking-wider">{t('home.emergency.instructions')}</span>
                        <span className="text-sm text-red-200">{t('home.emergency.instructions_sub')}</span>
                    </Link>

                    <Link
                        href={`/${locale as string}/map`}
                        className="group flex flex-col items-center justify-center gap-2 rounded-xl bg-white/10 px-6 py-6 text-white backdrop-blur-sm transition-all hover:bg-white/20 hover:shadow-lg border border-white/20"
                    >
                        <Map className="size-8 mb-2 opacity-90 group-hover:scale-110 transition-transform" />
                        <span className="text-lg font-bold uppercase tracking-wider">{t('home.emergency.map')}</span>
                        <span className="text-sm text-red-200">{t('home.emergency.map_sub')}</span>
                    </Link>
                </div>

                <div className="mt-8">
                    <a
                        href="tel:112"
                        className="inline-flex items-center gap-3 rounded-full bg-white px-10 py-4 text-lg font-extrabold text-red-700 shadow-xl transition-all hover:scale-105 hover:bg-gray-100"
                    >
                        <Phone className="size-6 animate-pulse" />
                        {t('home.hero.emergency_call')} (112)
                    </a>
                </div>
            </div>
        </section>
    );
}
