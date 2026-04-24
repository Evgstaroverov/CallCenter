//
import './bootstrap.js';
import { createApp } from 'vue';
import TelegramMessages from './components/TelegramMessages.vue';


const app = createApp({
    components: {
        'telegram-messages': TelegramMessages
    }
});

app.component('telegram-messages', TelegramMessages);
app.mount('#app');