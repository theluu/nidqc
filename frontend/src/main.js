/**
 * @file
 * Entry của Vue SPA (ADR-003). Convert design -> Vue, backend Drupal qua JSON:API.
 */
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

createApp(App).use(router).mount('#app');
