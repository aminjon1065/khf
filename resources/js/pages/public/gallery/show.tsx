import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';
import { index as galleryIndex } from '@/routes/gallery';

type Photo = { id: number; url: string; thumb: string };

type Gallery = {
    title: string;
    description: string | null;
    date: string | null;
    photos: Photo[];
};

export default function GalleryShow({ gallery }: { gallery: Gallery }) {
    const { locale } = usePage().props;
    const { t } = useTranslations();

    return (
        <>
            <Head title={gallery.title} />

            <Link
                href={galleryIndex({ locale }).url}
                className="text-sm text-primary hover:underline"
            >
                {t('gallery.back')}
            </Link>

            <h1 className="mt-3 text-3xl leading-tight font-semibold">
                {gallery.title}
            </h1>
            <p className="mt-1 text-sm text-muted-foreground">
                {gallery.date} ·{' '}
                {t('gallery.photos', { count: gallery.photos.length })}
            </p>

            {gallery.description && (
                <p className="mt-4 text-muted-foreground">
                    {gallery.description}
                </p>
            )}

            <div className="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                {gallery.photos.map((photo) => (
                    <a
                        key={photo.id}
                        href={photo.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="block overflow-hidden rounded-lg border"
                    >
                        <img
                            src={photo.thumb}
                            alt={gallery.title}
                            loading="lazy"
                            className="aspect-[3/2] w-full object-cover transition-transform hover:scale-105"
                        />
                    </a>
                ))}
            </div>
        </>
    );
}
