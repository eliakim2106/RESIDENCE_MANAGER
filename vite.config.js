import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Site public
                'resources/css/site.css',
                'resources/js/site.js',
                // Administration
                'resources/css/admin.css',
                'resources/css/admin/etablissement.css',
                'resources/js/admin.js',
                'resources/js/admin/types.js',
                'resources/js/admin/equipement.js',
                'resources/js/admin/etablissement.js',
                'resources/js/admin/unite.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
