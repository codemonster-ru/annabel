import { readdir, readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath, URL } from 'node:url';

export function cmsProjectRoot(importMetaUrl) {
  return fileURLToPath(new URL('../../..', importMetaUrl));
}

export function cmsModuleRoot(importMetaUrl) {
  return fileURLToPath(new URL('.', importMetaUrl));
}

export function vueforgeAliases(importMetaUrl) {
  const vendorRoot = fileURLToPath(
    new URL('../../../node_modules/@codemonster-ru/', new URL('.', importMetaUrl)),
  );

  return [
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
  ];
}

export function verifyViteBundlePlugin({ name, assetsRoot, entrypoint }) {
  return {
    name: `verify-${name}-bundle`,
    apply: 'build',
    async writeBundle() {
      const manifestPath = path.join(assetsRoot, '.vite/manifest.json');
      const manifest = JSON.parse(await readFile(manifestPath, 'utf8'));
      const entry = manifest[entrypoint];

      if (!entry || typeof entry.file !== 'string') {
        throw new Error(`${name} Vite manifest is missing ${entrypoint}.`);
      }

      for (const bundlePath of await listJavaScriptBundles(assetsRoot)) {
        const bundleContents = await readFile(bundlePath, 'utf8');

        if (bundleContents.includes('@codemonster-ru/')) {
          throw new Error(
            `${name} bundle contains unresolved bare imports: ${path.relative(assetsRoot, bundlePath)}`,
          );
        }
      }

      console.log(`Verified ${name} bundle: ${entry.file}`);
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
