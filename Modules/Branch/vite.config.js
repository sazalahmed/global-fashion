import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    build: {
        outDir: '../../public/build-branch',
        lib: {
            entry: 'resources/assets/js/app.js',
            name: 'branch',
        },
    },
    plugins: [
        laravel({
            publicDirectory: '../../public',
            buildDirectory: 'build-branch',
            input: [
                __dirname + '/resources/assets/sass/app.scss',
                __dirname + '/resources/assets/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
