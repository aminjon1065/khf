import { useEffect, useState } from 'react';
import { Eye, EyeOff, Image, Type } from 'lucide-react';

type FontSize = 'normal' | 'large' | 'xl';
type ContrastMode = 'normal' | 'monochrome' | 'inverted' | 'blueyellow';
type ImagesMode = 'normal' | 'grayscale' | 'hidden';

export function AccessibilityToolbar({ onClose }: { onClose: () => void }) {
    const [fontSize, setFontSize] = useState<FontSize>('normal');
    const [contrast, setContrast] = useState<ContrastMode>('normal');
    const [imagesMode, setImagesMode] = useState<ImagesMode>('normal');

    // Load initial settings from localStorage on mount
    useEffect(() => {
        const storedSize = (localStorage.getItem('a11y-font-size') as FontSize) || 'normal';
        const storedContrast = (localStorage.getItem('a11y-contrast') as ContrastMode) || 'normal';
        const storedImages = (localStorage.getItem('a11y-images') as ImagesMode) || 'normal';

        setFontSize(storedSize);
        setContrast(storedContrast);
        setImagesMode(storedImages);
    }, []);

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
        root.classList.remove('a11y-contrast-monochrome', 'a11y-contrast-inverted', 'a11y-contrast-blueyellow');
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
        <div className="w-full bg-[#1e293b] text-slate-100 border-b border-slate-700 py-3 px-4 shadow-inner transition-all duration-300 print:hidden z-55">
            <div className="mx-auto max-w-6xl flex flex-wrap gap-6 items-center justify-between">
                <div className="flex flex-wrap items-center gap-6">
                    {/* Font Size Panel */}
                    <div className="flex items-center gap-3">
                        <Type className="size-4 text-slate-400" />
                        <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Шрифт:</span>
                        <div className="flex rounded-md bg-slate-800 p-0.5 border border-slate-700">
                            <button
                                type="button"
                                onClick={() => setFontSize('normal')}
                                className={`rounded px-3 py-1 text-xs font-semibold transition-all cursor-pointer ${
                                    fontSize === 'normal'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'hover:text-white text-slate-400'
                                }`}
                            >
                                А (Стандарт)
                            </button>
                            <button
                                type="button"
                                onClick={() => setFontSize('large')}
                                className={`rounded px-3 py-1 text-xs font-semibold transition-all cursor-pointer ${
                                    fontSize === 'large'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'hover:text-white text-slate-400'
                                }`}
                            >
                                А+ (Крупный)
                            </button>
                            <button
                                type="button"
                                onClick={() => setFontSize('xl')}
                                className={`rounded px-3 py-1 text-xs font-semibold transition-all cursor-pointer ${
                                    fontSize === 'xl'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'hover:text-white text-slate-400'
                                }`}
                            >
                                А++ (Огромный)
                            </button>
                        </div>
                    </div>

                    {/* Contrast Panel */}
                    <div className="flex items-center gap-3">
                        <Eye className="size-4 text-slate-400" />
                        <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Цвета:</span>
                        <div className="flex rounded-md bg-slate-800 p-0.5 border border-slate-700">
                            <button
                                type="button"
                                onClick={() => setContrast('normal')}
                                className={`rounded px-3 py-1 text-xs font-semibold transition-all cursor-pointer ${
                                    contrast === 'normal'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'hover:text-white text-slate-400'
                                }`}
                            >
                                Обычные
                            </button>
                            <button
                                type="button"
                                onClick={() => setContrast('monochrome')}
                                className={`rounded px-3 py-1 text-xs font-semibold transition-all cursor-pointer border border-transparent ${
                                    contrast === 'monochrome'
                                        ? 'bg-white text-black font-bold border-slate-400 shadow-sm'
                                        : 'hover:text-white text-slate-400'
                                }`}
                            >
                                Ч/Б
                            </button>
                            <button
                                type="button"
                                onClick={() => setContrast('inverted')}
                                className={`rounded px-3 py-1 text-xs font-semibold transition-all cursor-pointer border border-transparent ${
                                    contrast === 'inverted'
                                        ? 'bg-black text-white font-bold border-slate-700 shadow-sm'
                                        : 'hover:text-white text-slate-400'
                                }`}
                            >
                                Инверсия
                            </button>
                            <button
                                type="button"
                                onClick={() => setContrast('blueyellow')}
                                className={`rounded px-3 py-1 text-xs font-semibold transition-all cursor-pointer border border-transparent ${
                                    contrast === 'blueyellow'
                                        ? 'bg-[#0000ff] text-[#ffff00] font-bold border-blue-900 shadow-sm'
                                        : 'hover:text-white text-slate-400'
                                }`}
                            >
                                Сине-желтый
                            </button>
                        </div>
                    </div>

                    {/* Images Mode Panel */}
                    <div className="flex items-center gap-3">
                        <Image className="size-4 text-slate-400" />
                        <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Картинки:</span>
                        <div className="flex rounded-md bg-slate-800 p-0.5 border border-slate-700">
                            <button
                                type="button"
                                onClick={() => setImagesMode('normal')}
                                className={`rounded px-3 py-1 text-xs font-semibold transition-all cursor-pointer ${
                                    imagesMode === 'normal'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'hover:text-white text-slate-400'
                                }`}
                            >
                                Вкл
                            </button>
                            <button
                                type="button"
                                onClick={() => setImagesMode('grayscale')}
                                className={`rounded px-3 py-1 text-xs font-semibold transition-all cursor-pointer ${
                                    imagesMode === 'grayscale'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'hover:text-white text-slate-400'
                                }`}
                            >
                                Ч/Б
                            </button>
                            <button
                                type="button"
                                onClick={() => setImagesMode('hidden')}
                                className={`rounded px-3 py-1 text-xs font-semibold transition-all cursor-pointer ${
                                    imagesMode === 'hidden'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'hover:text-white text-slate-400'
                                }`}
                            >
                                Выкл
                            </button>
                        </div>
                    </div>
                </div>

                <div className="flex items-center gap-3 ml-auto">
                    <button
                        type="button"
                        onClick={resetAll}
                        className="rounded-md border border-slate-700 bg-slate-800 px-3 py-1 text-xs font-medium text-slate-300 transition-colors hover:bg-slate-700 hover:text-white cursor-pointer"
                    >
                        Сбросить настройки
                    </button>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-md bg-red-950/40 border border-red-900/60 hover:bg-red-900 hover:text-white px-3 py-1 text-xs font-medium text-red-200 transition-all cursor-pointer"
                    >
                        Закрыть панель
                    </button>
                </div>
            </div>
        </div>
    );
}
