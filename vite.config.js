import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

/*
 * Bundle della UI operatore. La build va nella cartella `public/` del modulo, che e' servita da
 * `public_html/assets/roosko` tramite symlink (`php artisan xot:module-link`): niente copie in
 * public_html. Il provider del pannello carica `resources/js/scanner.js` con
 * `Vite(..., 'assets/roosko')`, cioe' `public_html/assets/roosko/manifest.json` attraverso il link.
 * Gli asset compilati sono versionati: il server di produzione non ha bisogno di Node.
 */
export default defineConfig({
    build: {
        outDir: resolve(__dirname, 'public'),
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
