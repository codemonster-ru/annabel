import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

const moduleRoot = fileURLToPath(new URL('.', import.meta.url));
const projectRoot = fileURLToPath(new URL('../../..', import.meta.url));

export default defineConfig({
  root: moduleRoot,
  plugins: [vue()],
  publicDir: false,
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    watch: {
      ignored: [
        '**/.git/**',
        '**/node_modules/**',
        '**/vendor/**',
        '**/storage/**',
        '**/var/**',
      ],
    },
  },
  build: {
    outDir: `${projectRoot}/public/admin/assets`,
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: `${moduleRoot}/resources/js/main.js`,
      output: {
        entryFileNames: 'admin-[hash].js',
        chunkFileNames: '[name]-[hash].js',
        assetFileNames: '[name]-[hash][extname]',
      },
    },
  },
});
