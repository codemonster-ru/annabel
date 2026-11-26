import { readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath, URL } from 'node:url';

export function cmsProjectRoot(importMetaUrl) {
  return fileURLToPath(new URL('../../..', importMetaUrl));
}

export function cmsModuleRoot(importMetaUrl) {
  return fileURLToPath(new URL('.', importMetaUrl));
}

export function verifyViteBundlePlugin({ name, assetsRoot, entrypoint }) {
  return {
    name: `verify-${name}-bundle`,
    apply: 'build',
    async writeBundle(_options, bundle) {
      const manifestPath = path.join(assetsRoot, '.vite/manifest.json');
      const manifest = JSON.parse(await readFile(manifestPath, 'utf8'));
      const entry = manifest[entrypoint];

      if (!entry || typeof entry.file !== 'string') {
        throw new Error(`${name} Vite manifest is missing ${entrypoint}.`);
      }

      for (const chunk of Object.values(bundle)) {
        if (chunk.type !== 'chunk') {
          continue;
        }

        const bareImport = [...chunk.imports, ...chunk.dynamicImports]
          .find((specifier) => specifier.startsWith('@codemonster-ru/'));
        if (bareImport) {
          throw new Error(`${name} bundle contains unresolved bare import ${bareImport} in ${chunk.fileName}.`);
        }
      }

      console.log(`Verified ${name} bundle: ${entry.file}`);
    },
  };
}
