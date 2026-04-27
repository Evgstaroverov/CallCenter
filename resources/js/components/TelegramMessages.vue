<template>


    <div class="p-4 sm:p-6">
        <h1 class="text-xl sm:text-2xl font-bold mb-4">Чаты опеатора колл-центра:</h1>

        <!-- Список чатов -->
        <div v-if="!selectedChat" class="space-y-3">
            <div v-if="loading" class="text-gray-500 text-sm">Загрузка чатов...</div>
            
<div v-for="chat in chats" :key="chat.chat_id" 
    @click="openChat(chat.chat_id)"
    class="border rounded-lg p-3 sm:p-4 cursor-pointer hover:bg-gray-50 transition-colors">
    
    <div class="flex justify-between items-start">
        <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-sm sm:text-base truncate">
                {{ chat.user_name || 'Чат ' + chat.chat_id }}
            </h3>
            <p class="text-xs text-gray-500 truncate">
                {{ chat.last_message_preview || 'Нет сообщений' }}
            </p>
            <p class="text-xs text-gray-400">ID: {{ chat.chat_id }}</p>
        </div>
        <div class="text-right flex-shrink-0 ml-2">
            <span class="text-xs text-gray-400 block">{{ chat.last_message_time }}</span>
            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                {{ chat.message_count }}
            </span>
        </div>
    </div>
</div>

            <div v-if="!loading && chats.length === 0" class="text-gray-500 text-sm">
                Чатов пока нет
            </div>
        </div>

        <!-- Окно чата -->
        <div v-else>
            <!-- Кнопка назад -->
            <button @click="selectedChat = null" 
                class="mb-4 text-blue-500 hover:text-blue-700 text-sm sm:text-base flex items-center gap-1">
                ← Назад к списку чатов
            </button>

            <!-- Заголовок чата -->
            <h2 class="text-lg sm:text-xl font-bold mb-4">
                Чат: {{ selectedChat }}
            </h2>

            <!-- Форма отправки -->
            <div class="mb-6 p-3 sm:p-4 border rounded shadow-sm">
                <textarea v-model="newMessage" placeholder="Ваше сообщение..." 
                    class="border p-2 w-full rounded text-sm sm:text-base" rows="3"></textarea>
                <button @click="sendMessage" :disabled="sending" 
                    class="mt-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm sm:text-base w-full sm:w-auto">
                    {{ sending ? 'Отправка...' : 'Отправить' }}
                </button>
            </div>

            <!-- Сообщения -->
            <div v-if="isLocal" class="text-orange-500 mb-2 text-xs sm:text-sm italic">
                ⚠️ Telegram недоступен. Показаны данные из базы.
            </div>

            <div v-if="loadingMessages" class="text-gray-500 text-sm">Загрузка сообщений...</div>

            <ul v-else-if="messages.length > 0" class="space-y-2">
                <li v-for="item in messages" :key="item.id" 
                    class="border-b pb-2 text-sm sm:text-base">
                    
                    <div class="flex flex-col gap-1">
                        <div class="flex flex-wrap items-baseline gap-1">
                            <span :class="item.is_outbound ? 'text-green-600' : 'text-black'" class="font-semibold">
                                {{ item.user_name }}:
                            </span>
                            <span class="text-gray-700 break-words">{{ item.text || '[Нет текста]' }}</span>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs text-gray-400">📅 {{ item.formatted_date }}</span>
                            <span class="text-xs text-gray-400 italic">({{ item.relative_time }})</span>
                            <span v-if="isLocal" class="text-[10px] text-gray-400 border px-1 rounded">
                                сохранено локально
                            </span>
                        </div>
                    </div>
                </li>
            </ul>

            <div v-else class="text-red-500 text-sm">Сообщений нет.</div>

            <button @click="fetchMessages" 
                class="mt-6 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded shadow text-sm sm:text-base w-full sm:w-auto">
                Обновить сообщения
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const chats = ref([]);
const messages = ref([]);
const selectedChat = ref(null);
const loading = ref(true);
const loadingMessages = ref(false);
const isLocal = ref(false);
const newMessage = ref('');
const sending = ref(false);

// Загрузка списка чатов
const fetchChats = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/chats');
        chats.value = response.data.chats;
    } catch (error) {
        console.error("Ошибка при загрузке чатов:", error);
    } finally {
        loading.value = false;
    }
};

// Открыть чат и загрузить сообщения
const openChat = (chatId) => {
    selectedChat.value = chatId;
    fetchMessages();
};

// Загрузка сообщений выбранного чата
const fetchMessages = async () => {
    if (!selectedChat.value) return;
    
    loadingMessages.value = true;
    try {
        const response = await axios.get(`/messages/${selectedChat.value}`);
        messages.value = response.data.messages;
        isLocal.value = response.data.is_local;
    } catch (error) {
        console.error("Ошибка при загрузке сообщений:", error);
    } finally {
        loadingMessages.value = false;
    }
};

// Отправка сообщения
const sendMessage = async () => {
    if (!newMessage.value || !selectedChat.value) return;
    
    sending.value = true;
    try {
        await axios.post('/send-message', {
            chat_id: selectedChat.value,
            text: newMessage.value
        });
        newMessage.value = '';
        fetchMessages(); // Обновляем список сообщений
    } catch (e) {
        alert('Ошибка при отправке');
    } finally {
        sending.value = false;
    }
};

onMounted(fetchChats);
</script>