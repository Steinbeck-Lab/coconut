import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import path from 'path'

export default defineConfig({
    build: {
        sourcemap: true,
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/dashboard/theme.css',
                'resources/css/filament/control-panel/theme.css'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
          '~font' : path.resolve(__dirname,'resources/fonts')
        }
    },
    server: {
        host: '0.0.0.0', // Required for Docker - security handled in docker-compose.yml
        https: false,
        hmr: {
            host: 'localhost',
        },
    },
});
