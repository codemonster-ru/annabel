import { createApp } from 'vue';
import AdminApp from './AdminApp.vue';
import '../css/admin.css';

createApp(AdminApp, {
  boot: window.__ANNABEL_CMS_ADMIN__ || {},
}).mount('#admin-app');
