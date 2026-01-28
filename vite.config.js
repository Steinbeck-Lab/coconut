import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
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
                'resources/css/filament/dashboard/theme.css'
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
          '~font' : path.resolve(__dirname,'resources/fonts')
        }
    },
    server: {
        host: '0.0.0.0', // Listen on all interfaces for Docker
        https: false,
        hmr: {
            host: 'localhost',
        },
    },
});
