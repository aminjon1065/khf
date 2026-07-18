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
            injectRegister: null,
            outDir: 'public',
            filename: 'sw.js',
            scope: '/',
            base: '/',
            buildBase: '/build/',
            includeAssets: [
                'images/pwa-192.png',
                'images/pwa-512.png',
                'images/apple-touch-icon.png',
                'images/favicon-32.png',
            ],
            manifest: false,
            workbox: {
                globPatterns: [
                    'build/assets/**/*.{js,css,woff2,woff}',
                    'images/pwa-*.png',
                    'images/apple-touch-icon.png',
                    'images/favicon-32.png',
                    'manifest.webmanifest',
                ],
                importScripts: ['/push-sw.js'],
                navigateFallback: null,
            },
        }),
    ],
});
