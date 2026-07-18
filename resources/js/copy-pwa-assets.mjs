import { copyFile, mkdir } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const currentDirectory = dirname(fileURLToPath(import.meta.url));
const sourceDirectory = resolve(currentDirectory, '../static/logo');
const destinationDirectory = resolve(currentDirectory, '../../public/images');
const assets = [
    'pwa-192.png',
    'pwa-512.png',
    'apple-touch-icon.png',
    'favicon-32.png',
];

await mkdir(destinationDirectory, { recursive: true });
await Promise.all(
    assets.map((asset) =>
        copyFile(
            resolve(sourceDirectory, asset),
            resolve(destinationDirectory, asset),
        ),
    ),
);
