import { createApp } from 'vue';
import '@codemonster-ru/ui-tokens/tokens.css';
import '@codemonster-ru/ui-css/styles.css';
import AdminApp from './AdminApp.vue';
import '../css/admin.css';
import faviconUrl from '../images/codemonster-icon.svg';

void faviconUrl;

const boot = window.__ANNABEL_CMS_ADMIN__ || {};

const app = createApp(AdminApp, {
  boot,
});

app.mount('#admin-app');
