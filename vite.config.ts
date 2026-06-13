import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { VitePWA } from 'vite-plugin-pwa';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            fonts: [
                // Inter — full Cyrillic incl. Tajik glyphs (ҷ/ҳ/қ/ғ/ӯ/ӣ); self-hosted via Bunny
                // (no Google Fonts) for data sovereignty (ТЗ §11.3, §12.6).
                bunny('Inter', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
        inertia(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            outDir: 'public/build',
            buildBase: '/build/',
            manifest: {
                name: 'КЧС',
                short_name: 'КЧС',
                description: 'Портал Комитета по чрезвычайным ситуациям',
                theme_color: '#ffffff',
                icons: [
                    {
                        src: '/build/icon-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/build/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                    },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,woff2,png,svg}'],
                navigateFallback: null,
            },
        }),
    ],
});
