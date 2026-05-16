// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Entry admin del builder: usare il file SCSS reale presente nel progetto.
                'resources/scss/page_builder.scss',
                'resources/js/admin/pageBuilder.js',
            ],
            refresh: true,
        }),
    ],
    // Permetti import da /plugins quando sei in dev.
    server: {
        fs: { allow: ['resources', 'plugins'] },
    },
    resolve: {
        alias: { '@plugins': '/plugins' },
    },
});
