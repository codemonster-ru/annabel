import { createApp } from 'vue';
import VueForgeLayouts from '@codemonster-ru/vueforge-layouts';
import '@codemonster-ru/ui-tokens/tokens.css';
import '@codemonster-ru/ui-css/styles.css';
import SetupApp from './SetupApp.vue';
import '../css/setup.css';

const app = createApp(SetupApp, {
  boot: window.__ANNABEL_CMS_SETUP__ || {},
});

app.use(VueForgeLayouts, {
  defaultTheme: 'system',
  themeStorageKey: 'annabel-setup-theme',
});

app.mount('#setup-app');
