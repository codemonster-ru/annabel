import { readdir, readFile } from 'node:fs/promises';
import path from 'node:path';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

const moduleRoot = fileURLToPath(new URL('.', import.meta.url));
const projectRoot = fileURLToPath(new URL('../../..', import.meta.url));
const vendorRoot = fileURLToPath(
  new URL('../../../node_modules/@codemonster-ru/', new URL('.', import.meta.url)),
);

export default defineConfig({
  root: moduleRoot,
  base: '/setup/assets/',
  resolve: {
    alias: [
      {
        find: /^@codemonster-ru\/vueforge-core$/,
        replacement: `${vendorRoot}/vueforge-core/dist/vueforge-core.js`,
      },
      {
        find: /^@codemonster-ru\/vueforge-core\/foundation$/,
        replacement: `${vendorRoot}/vueforge-core/dist/foundation-api.js`,
      },
      {
        find: /^@codemonster-ru\/vueforge-core\/(.+)$/,
        replacement: `${vendorRoot}/vueforge-core/dist/auto/$1.js`,
      },
      {
        find: /^@codemonster-ru\/vueforge-layouts$/,
        replacement: `${vendorRoot}/vueforge-layouts/dist/index.js`,
      },
      {
        find: /^@codemonster-ru\/vueforge-layouts\/(.+)$/,
        replacement: `${vendorRoot}/vueforge-layouts/dist/auto/$1.js`,
      },
      {
        find: /^@codemonster-ru\/vueforge-theme$/,
        replacement: `${vendorRoot}/vueforge-theme/dist/index.js`,
      },
      {
        find: /^@codemonster-ru\/vueforge-icons$/,
        replacement: `${vendorRoot}/vueforge-icons/dist/index.ts.mjs`,
      },
      {
        find: /^@codemonster-ru\/floater\.js$/,
        replacement: `${vendorRoot}/floater.js/dist/index.mjs`,
      },
    ],
  },
  plugins: [
    vue(),
    verifySetupBundlePlugin(path.join(projectRoot, 'public/setup/assets')),
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
    outDir: `${projectRoot}/public/setup/assets`,
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

function verifySetupBundlePlugin(assetsRoot) {
  return {
    name: 'verify-setup-bundle',
    apply: 'build',
    async closeBundle() {
      const manifestPath = path.join(assetsRoot, '.vite/manifest.json');
      const manifest = JSON.parse(await readFile(manifestPath, 'utf8'));
      const entry = manifest['resources/js/main.js'];

      if (!entry || typeof entry.file !== 'string') {
        throw new Error('Setup Vite manifest is missing resources/js/main.js.');
      }

      for (const bundlePath of await listJavaScriptBundles(assetsRoot)) {
        const bundleContents = await readFile(bundlePath, 'utf8');

        if (bundleContents.includes('@codemonster-ru/')) {
          throw new Error(
            `Setup bundle contains unresolved bare imports: ${path.relative(assetsRoot, bundlePath)}`,
          );
        }
      }

      console.log(`Verified setup bundle: ${entry.file}`);
    },
  };
}

async function listJavaScriptBundles(directory) {
  const entries = await readdir(directory, { withFileTypes: true });
  const bundles = [];

  for (const entry of entries) {
    const resolvedPath = path.join(directory, entry.name);

    if (entry.isDirectory()) {
      bundles.push(...await listJavaScriptBundles(resolvedPath));
      continue;
    }

    if (entry.isFile() && entry.name.endsWith('.js')) {
      bundles.push(resolvedPath);
    }
  }

  return bundles;
}
