import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pages/dashboard.js',
                'resources/js/pages/admin-dashboard.js',
                'resources/js/pages/reports.js',
                'resources/js/pages/application-reports.js',
                'resources/js/pages/general-reports.js',
                'resources/js/pages/by-list-reports.js',
                'resources/js/pages/analytical-reports.js',
                'resources/js/pages/geo-cascade.js',
                'resources/js/pages/landing.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        target: 'es2020',
        cssMinify: true,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules/chart.js')) {
                        return 'chart';
                    }
                    if (id.includes('node_modules/alpinejs')) {
                        return 'alpine';
                    }
                },
            },
        },
    },
    server: {
        host: '127.0.0.1',
        open: false,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
