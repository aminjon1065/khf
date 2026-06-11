import { Head, Link, usePage } from '@inertiajs/react';
import { Phone } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { welcome } from '@/routes';

type PageProps = {
    status: number;
};

const KNOWN = [403, 404, 419, 429, 500, 503] as const;

export default function ErrorPage({ status }: PageProps) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

    const key = (KNOWN as readonly number[]).includes(status)
        ? String(status)
        : '500';
    const title = t(`errors.${key}.title`);
    const message = t(`errors.${key}.message`);

    return (
        <>
            <Head title={`${status} — ${title}`} />

            <div className="mx-auto flex max-w-xl flex-col items-center gap-4 py-16 text-center">
                <p className="text-6xl font-semibold tracking-tight text-primary">
                    {status}
                </p>
                <h1 className="text-2xl font-semibold">{title}</h1>
                <p className="text-muted-foreground">{message}</p>

                <div className="mt-4 flex flex-wrap items-center justify-center gap-3">
                    <Button asChild>
                        <Link href={welcome({ locale }).url}>
                            {t('nav.home')}
                        </Link>
                    </Button>
                    <a
                        href="tel:112"
                        className="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium"
                    >
                        <Phone className="size-4" />
                        {t('home.hero.emergency_call')}
                    </a>
                </div>
            </div>
        </>
    );
}
