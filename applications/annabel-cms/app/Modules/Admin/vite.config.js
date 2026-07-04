import path from 'node:path';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { cmsModuleRoot, cmsProjectRoot, verifyViteBundlePlugin, vueforgeAliases } from '../../../vite.shared.js';

const moduleRoot = cmsModuleRoot(import.meta.url);
const projectRoot = cmsProjectRoot(import.meta.url);
const assetsRoot = path.join(projectRoot, 'public/admin/assets');

export default defineConfig({
  root: moduleRoot,
  base: '/admin/assets/',
  resolve: {
    alias: vueforgeAliases(import.meta.url),
  },
  plugins: [
    vue(),
    verifyViteBundlePlugin({
      name: 'Admin',
      assetsRoot,
      entrypoint: 'resources/js/main.js',
    }),
  ],
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
    outDir: assetsRoot,
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
