import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

/*
 * Bundle della UI operatore. `outDir` DEVE coincidere con `publicDirectory` + `buildDirectory`
 * del plugin Laravel (stesso motivo documentato nel modulo Geo): il provider del pannello
 * carica `resources/js/scanner.js` con `Vite(..., 'assets/roosko')`.
 *
 * Il manifest PWA (`public/manifest.webmanifest`) e l'icona si pubblicano con
 * `php artisan vendor:publish --tag=roosko-assets`; il service worker e' servito dalla
 * route `roosko.service-worker`.
 */
export default defineConfig({
    build: {
        outDir: '../../../public_html/assets/roosko',
        emptyOutDir: false,
        manifest: 'manifest.json',
        rollupOptions: {
            output: {
                entryFileNames: 'assets/[name].js',
                chunkFileNames: 'assets/[name].js',
                assetFileNames: 'assets/[name].[ext]',
            },
        },
    },
    plugins: [
        laravel({
            publicDirectory: '../../../public_html',
            buildDirectory: 'assets/roosko',
            input: [
                resolve(__dirname, 'resources/js/scanner.js'),
            ],
            refresh: true,
        }),
    ],
});
