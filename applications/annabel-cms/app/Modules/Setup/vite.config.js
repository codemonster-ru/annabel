import path from 'node:path';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { cmsModuleRoot, cmsProjectRoot, verifyViteBundlePlugin, vueforgeAliases } from '../../../vite.shared.js';

const moduleRoot = cmsModuleRoot(import.meta.url);
const projectRoot = cmsProjectRoot(import.meta.url);
const assetsRoot = process.env.ANNABEL_CMS_SETUP_ASSETS_ROOT
  ?? path.join(projectRoot, 'public/setup/assets');

export default defineConfig({
  root: moduleRoot,
  base: '/setup/assets/',
  resolve: {
    alias: vueforgeAliases(import.meta.url),
  },
  plugins: [
    vue(),
    verifyViteBundlePlugin({
      name: 'Setup',
      assetsRoot,
      entrypoint: 'resources/js/main.js',
    }),
  ],
  publicDir: false,
  server: {
    host: '0.0.0.0',
    port: 5174,
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
      input: {
        main: `${moduleRoot}/resources/js/main.js`,
        favicon: `${moduleRoot}/resources/images/setup-brand.svg`,
      },
      output: {
        entryFileNames: 'setup-[hash].js',
        chunkFileNames: '[name]-[hash].js',
        assetFileNames: '[name]-[hash][extname]',
      },
    },
  },
});
