import { createApp } from 'vue';
import VueForgeLayouts from '@codemonster-ru/vueforge-layouts';
import AdminApp from './AdminApp.vue';
import { configureDateTime } from './support/dateTime';
import '../css/admin.css';
import faviconUrl from '../images/codemonster-icon.svg';

void faviconUrl;

const boot = window.__ANNABEL_CMS_ADMIN__ || {};
configureDateTime(boot);

const app = createApp(AdminApp, {
  boot,
});

app.use(VueForgeLayouts, {
  defaultTheme: 'system',
  themeStorageKey: 'annabel-admin-theme',
});

app.mount('#admin-app');
