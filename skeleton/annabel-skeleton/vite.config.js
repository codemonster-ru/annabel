import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        manifest: 'manifest.json',
        outDir: 'public/build',
        rollupOptions: {
            input: ['resources/js/app.js'],
        },
    },
    publicDir: false,
    server: {
        origin: 'http://localhost:5173',
        watch: {
            ignored: [
                '**/.git/**',
                '**/node_modules/**',
                '**/vendor/**',
                '**/public/build/**',
                '**/storage/**',
                '**/var/**',
            ],
        },
    },
});
