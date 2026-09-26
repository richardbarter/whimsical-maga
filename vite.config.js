import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    server: {
        hmr: {
            host: 'localhost',
        },
        watch: {
            // Docker Desktop doesn't forward file-change events from Windows bind mounts,
            // so the vite container polls instead (set in docker-compose.yml).
            usePolling: process.env.WATCH_USE_POLLING === 'true',
            // Polling stats every watched file; skip the large PHP-side directories.
            ignored: ['**/vendor/**', '**/storage/**'],
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.ts',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
});
