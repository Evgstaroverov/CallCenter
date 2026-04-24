<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">Сообщения из Telegram:</h1>

        <div v-if="loading" class="text-gray-500">Загрузка данных...</div>

        <ul v-else-if="messages.length > 0" class="space-y-2">
            <li v-for="item in messages" :key="item.update_id" class="border-b pb-2">
                <span class="font-semibold">{{ item.message?.from?.first_name || 'Аноним' }}:</span>
                <span class="ml-2 text-gray-700">{{ item.message?.text || '[Нет текста]' }}</span>
            </li>
        </ul>

        <div v-else class="text-red-500">Сообщений нет. Напишите боту!</div>

        <button @click="fetchMessages" 
                class="mt-6 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded shadow">
            Обновить вручную
        </button>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const messages = ref([]);
const loading = ref(true);

const fetchMessages = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/telegram-messages');
        messages.value = response.data;
    } catch (error) {
        console.error("Ошибка при загрузке:", error);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchMessages);
</script>