import { Eye, Image, Type } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useTranslations } from '@/hooks/use-translations';

type FontSize = 'normal' | 'large' | 'xl';
type ContrastMode = 'normal' | 'monochrome' | 'inverted' | 'blueyellow';
type ImagesMode = 'normal' | 'grayscale' | 'hidden';

/**
 * Read a persisted preference. This toolbar only mounts client-side (on user toggle), so reading
 * localStorage in a lazy initializer is safe and avoids a setState-in-effect render cascade.
 */
function readStored<T extends string>(key: string, fallback: T): T {
    if (typeof window === 'undefined') {
        return fallback;
    }

    return (localStorage.getItem(key) as T) || fallback;
}

export function AccessibilityToolbar({ onClose }: { onClose: () => void }) {
    const { t } = useTranslations();
    const [fontSize, setFontSize] = useState<FontSize>(() =>
        readStored('a11y-font-size', 'normal'),
    );
    const [contrast, setContrast] = useState<ContrastMode>(() =>
        readStored('a11y-contrast', 'normal'),
    );
    const [imagesMode, setImagesMode] = useState<ImagesMode>(() =>
        readStored('a11y-images', 'normal'),
    );

    // Apply classes to documentElement when settings change
    useEffect(() => {
        const root = document.documentElement;

        // Font Size
        root.classList.remove('a11y-size-large', 'a11y-size-xl');

        if (fontSize === 'large') {
            root.classList.add('a11y-size-large');
        } else if (fontSize === 'xl') {
            root.classList.add('a11y-size-xl');
        }

        localStorage.setItem('a11y-font-size', fontSize);
    }, [fontSize]);

    useEffect(() => {
        const root = document.documentElement;

        // Contrast
        root.classList.remove(
            'a11y-contrast-monochrome',
            'a11y-contrast-inverted',
            'a11y-contrast-blueyellow',
        );

        if (contrast === 'monochrome') {
            root.classList.add('a11y-contrast-monochrome');
        } else if (contrast === 'inverted') {
            root.classList.add('a11y-contrast-inverted');
        } else if (contrast === 'blueyellow') {
            root.classList.add('a11y-contrast-blueyellow');
        }

        localStorage.setItem('a11y-contrast', contrast);
    }, [contrast]);

    useEffect(() => {
        const root = document.documentElement;

        // Images
        root.classList.remove('a11y-images-grayscale', 'a11y-images-hidden');

        if (imagesMode === 'grayscale') {
            root.classList.add('a11y-images-grayscale');
        } else if (imagesMode === 'hidden') {
            root.classList.add('a11y-images-hidden');
        }

        localStorage.setItem('a11y-images', imagesMode);
    }, [imagesMode]);

    const resetAll = () => {
        setFontSize('normal');
        setContrast('normal');
        setImagesMode('normal');
    };

    return (
        <div
            role="region"
            aria-label={t('a11y.open')}
            className="z-55 w-full border-b border-slate-700 bg-[#1e293b] px-4 py-3 text-slate-100 shadow-inner transition-all duration-300 print:hidden"
        >
            <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-6">
                <div className="flex flex-wrap items-center gap-6">
                    {/* Font Size Panel */}
                    <div className="flex items-center gap-3">
                        <Type className="size-4 text-slate-400" />
                        <span className="text-xs font-semibold tracking-wider text-slate-400 uppercase">
                            {t('a11y.font')}:
                        </span>
                        <div className="flex rounded-md border border-slate-700 bg-slate-800 p-0.5">
                            <button
                                type="button"
                                onClick={() => setFontSize('normal')}
                                className={`cursor-pointer rounded px-3 py-1 text-xs font-semibold transition-all ${
                                    fontSize === 'normal'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'text-slate-400 hover:text-white'
                                }`}
                            >
                                {t('a11y.size_normal')}
                            </button>
                            <button
                                type="button"
                                onClick={() => setFontSize('large')}
                                className={`cursor-pointer rounded px-3 py-1 text-xs font-semibold transition-all ${
                                    fontSize === 'large'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'text-slate-400 hover:text-white'
                                }`}
                            >
                                {t('a11y.size_large')}
                            </button>
                            <button
                                type="button"
                                onClick={() => setFontSize('xl')}
                                className={`cursor-pointer rounded px-3 py-1 text-xs font-semibold transition-all ${
                                    fontSize === 'xl'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'text-slate-400 hover:text-white'
                                }`}
                            >
                                {t('a11y.size_xl')}
                            </button>
                        </div>
                    </div>

                    {/* Contrast Panel */}
                    <div className="flex items-center gap-3">
                        <Eye className="size-4 text-slate-400" />
                        <span className="text-xs font-semibold tracking-wider text-slate-400 uppercase">
                            {t('a11y.colors')}:
                        </span>
                        <div className="flex rounded-md border border-slate-700 bg-slate-800 p-0.5">
                            <button
                                type="button"
                                onClick={() => setContrast('normal')}
                                className={`cursor-pointer rounded px-3 py-1 text-xs font-semibold transition-all ${
                                    contrast === 'normal'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'text-slate-400 hover:text-white'
                                }`}
                            >
                                {t('a11y.contrast_normal')}
                            </button>
                            <button
                                type="button"
                                onClick={() => setContrast('monochrome')}
                                className={`cursor-pointer rounded border border-transparent px-3 py-1 text-xs font-semibold transition-all ${
                                    contrast === 'monochrome'
                                        ? 'border-slate-400 bg-white font-bold text-black shadow-sm'
                                        : 'text-slate-400 hover:text-white'
                                }`}
                            >
                                {t('a11y.contrast_monochrome')}
                            </button>
                            <button
                                type="button"
                                onClick={() => setContrast('inverted')}
                                className={`cursor-pointer rounded border border-transparent px-3 py-1 text-xs font-semibold transition-all ${
                                    contrast === 'inverted'
                                        ? 'border-slate-700 bg-black font-bold text-white shadow-sm'
                                        : 'text-slate-400 hover:text-white'
                                }`}
                            >
                                {t('a11y.contrast_inverted')}
                            </button>
                            <button
                                type="button"
                                onClick={() => setContrast('blueyellow')}
                                className={`cursor-pointer rounded border border-transparent px-3 py-1 text-xs font-semibold transition-all ${
                                    contrast === 'blueyellow'
                                        ? 'border-blue-900 bg-[#0000ff] font-bold text-[#ffff00] shadow-sm'
                                        : 'text-slate-400 hover:text-white'
                                }`}
                            >
                                {t('a11y.contrast_blueyellow')}
                            </button>
                        </div>
                    </div>

                    {/* Images Mode Panel */}
                    <div className="flex items-center gap-3">
                        <Image className="size-4 text-slate-400" />
                        <span className="text-xs font-semibold tracking-wider text-slate-400 uppercase">
                            {t('a11y.images')}:
                        </span>
                        <div className="flex rounded-md border border-slate-700 bg-slate-800 p-0.5">
                            <button
                                type="button"
                                onClick={() => setImagesMode('normal')}
                                className={`cursor-pointer rounded px-3 py-1 text-xs font-semibold transition-all ${
                                    imagesMode === 'normal'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'text-slate-400 hover:text-white'
                                }`}
                            >
                                {t('a11y.images_on')}
                            </button>
                            <button
                                type="button"
                                onClick={() => setImagesMode('grayscale')}
                                className={`cursor-pointer rounded px-3 py-1 text-xs font-semibold transition-all ${
                                    imagesMode === 'grayscale'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'text-slate-400 hover:text-white'
                                }`}
                            >
                                {t('a11y.images_grayscale')}
                            </button>
                            <button
                                type="button"
                                onClick={() => setImagesMode('hidden')}
                                className={`cursor-pointer rounded px-3 py-1 text-xs font-semibold transition-all ${
                                    imagesMode === 'hidden'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'text-slate-400 hover:text-white'
                                }`}
                            >
                                {t('a11y.images_off')}
                            </button>
                        </div>
                    </div>
                </div>

                <div className="ml-auto flex items-center gap-3">
                    <button
                        type="button"
                        onClick={resetAll}
                        className="cursor-pointer rounded-md border border-slate-700 bg-slate-800 px-3 py-1 text-xs font-medium text-slate-300 transition-colors hover:bg-slate-700 hover:text-white"
                    >
                        {t('a11y.reset')}
                    </button>
                    <button
                        type="button"
                        onClick={onClose}
                        className="cursor-pointer rounded-md border border-red-900/60 bg-red-950/40 px-3 py-1 text-xs font-medium text-red-200 transition-all hover:bg-red-900 hover:text-white"
                    >
                        {t('a11y.close_panel')}
                    </button>
                </div>
            </div>
        </div>
    );
}
