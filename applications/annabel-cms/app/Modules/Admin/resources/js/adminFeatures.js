import { defineAsyncComponent } from 'vue';

const featureModules = import.meta.glob('../../../*/resources/js/admin.js', {
  eager: true,
});

const screens = {};

for (const [path, featureModule] of Object.entries(featureModules)) {
  for (const [id, loader] of Object.entries(featureModule.adminScreens || {})) {
    if (screens[id]) {
      throw new Error('Admin screen is registered more than once: ' + id + ' (' + path + ')');
    }

    if (typeof loader !== 'function') {
      throw new Error('Admin screen loader must be a function: ' + id + ' (' + path + ')');
    }

    screens[id] = defineAsyncComponent(loader);
  }
}

export function resolveAdminScreen(id) {
  return screens[id] || null;
}
