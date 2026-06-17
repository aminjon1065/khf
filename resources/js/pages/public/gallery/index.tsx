import { Head, Link, usePage } from '@inertiajs/react';
import { Image as ImageIcon } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';
import { show } from '@/routes/gallery';

type GalleryCard = {
    title: string | null;
    slug: string | null;
    description: string | null;
    cover_url: string | null;
    photos_count: number;
};

export default function GalleryIndex({
    galleries,
}: {
    galleries: GalleryCard[];
}) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('gallery.title')} />

            <h1 className="text-3xl font-semibold">{t('gallery.title')}</h1>
            <p className="mt-1 text-muted-foreground">
                {t('gallery.subtitle')}
            </p>

            {galleries.length === 0 ? (
                <p className="mt-8 text-muted-foreground">
                    {t('gallery.empty')}
                </p>
            ) : (
                <div className="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {galleries.map((gallery) => (
                        <Link
                            key={gallery.slug}
                            href={
                                show({ locale, slug: gallery.slug ?? '' }).url
                            }
                            className="group flex flex-col overflow-hidden rounded-lg border transition-shadow hover:shadow-md"
                        >
                            <div className="flex aspect-video w-full items-center justify-center bg-muted">
                                {gallery.cover_url ? (
                                    <img
                                        src={gallery.cover_url}
                                        alt={gallery.title ?? ''}
                                        className="h-full w-full object-cover transition-transform group-hover:scale-105"
                                    />
                                ) : (
                                    <ImageIcon className="size-10 text-muted-foreground/40" />
                                )}
                            </div>
                            <div className="flex flex-1 flex-col gap-1 p-4">
                                <h2 className="leading-snug font-semibold group-hover:text-primary">
                                    {gallery.title}
                                </h2>
                                <p className="text-xs text-muted-foreground">
                                    {t('gallery.photos', {
                                        count: gallery.photos_count,
                                    })}
                                </p>
                                {gallery.description && (
                                    <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                        {gallery.description}
                                    </p>
                                )}
                            </div>
                        </Link>
                    ))}
                </div>
            )}
        </>
    );
}
