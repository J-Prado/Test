import { createApp } from 'vue';
import App from './components/App.vue';
import router from './router';
import { restore } from './api';

// Restore any existing session before mounting so guards see the user.
restore().finally(() => {
    createApp(App).use(router).mount('#app');
});
