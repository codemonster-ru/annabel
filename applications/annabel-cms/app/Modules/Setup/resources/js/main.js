import { createApp } from 'vue';
import '@codemonster-ru/ui-tokens/tokens.css';
import '@codemonster-ru/ui-css/styles.css';
import SetupApp from './SetupApp.vue';
import '../css/setup.css';

const app = createApp(SetupApp, {
  boot: window.__ANNABEL_CMS_SETUP__ || {},
});

app.mount('#setup-app');
