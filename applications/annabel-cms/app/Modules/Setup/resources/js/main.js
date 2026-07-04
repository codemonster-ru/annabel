import { createApp } from 'vue';
import VueForgeLayouts from '@codemonster-ru/vueforge-layouts';
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
