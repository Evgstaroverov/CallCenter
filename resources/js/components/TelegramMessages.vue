<template>
    <div class="p-6">
        <!-- Форма (добавлена сверху) -->
        <div class="mb-8 p-4 border rounded shadow-sm">
            <input v-model="chatId" type="text" placeholder="ID чата" class="border p-2 mb-2 w-full rounded">
            <textarea v-model="newMessage" placeholder="Ваше сообщение..." class="border p-2 w-full rounded"></textarea>
            <button @click="sendMessage" :disabled="sending" class="mt-2 bg-green-500 text-white px-4 py-2 rounded">
                {{ sending ? 'Отправка...' : 'Отправить' }}
            </button>
        </div>

        <h1 class="text-2xl font-bold mb-4">Сообщения из Telegram:</h1>

        <div v-if="isLocal" class="text-orange-500 mb-2 text-sm italic">
            ⚠️ Telegram недоступен. Показаны данные из базы.
        </div>

        <div v-if="loading" class="text-gray-500">Загрузка данных...</div>

        <ul v-else-if="messages.length > 0" class="space-y-2">
            <li v-for="item in messages" :key="item.id" 
                @click="chatId = item.chat_id"
                class="border-b pb-2 cursor-pointer hover:bg-gray-50">
                
                <span :class="item.is_outbound ? 'text-green-600' : 'text-black'" class="font-semibold">
                    {{ item.user_name }}:
                </span>
                <span class="ml-2 text-gray-700">{{ item.text || '[Нет текста]' }}</span>
                
                <!-- Метка локальности -->
                <span v-if="isLocal" class="ml-2 text-[10px] text-gray-400 border px-1">сохранено локально</span>
            </li>
        </ul>

        <div v-else class="text-red-500">Сообщений нет.</div>

        <button @click="fetchMessages" class="mt-6 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded shadow">
            Обновить вручную
        </button>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const messages = ref([]);
const loading = ref(true);
const isLocal = ref(false);
const newMessage = ref('');
const chatId = ref('');
const sending = ref(false);

const fetchMessages = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/telegram-messages');
        // Принимаем новый формат данных из контроллера
        messages.value = response.data.messages;
        isLocal.value = response.data.is_local;
    } catch (error) {
        console.error("Ошибка при загрузке:", error);
    } finally {
        loading.value = false;
    }
};

const sendMessage = async () => {
    if (!newMessage.value || !chatId.value) return;
    sending.value = true;
    try {
        await axios.post('/send-message', {
            chat_id: chatId.value,
            text: newMessage.value
        });
        newMessage.value = '';
        fetchMessages(); // Сразу обновляем список
    } catch (e) {
        alert('Ошибка при отправке');
    } finally {
        sending.value = false;
    }
};

onMounted(fetchMessages);
</script>