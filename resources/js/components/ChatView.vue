<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Навигация -->
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <router-link to="/chats" class="text-xl font-bold text-gray-900">
                            Чаты оператора колл-центра
                        </router-link>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">{{ auth.state.user?.name }}</span>
                        <button 
                            @click="handleLogout"
                            class="text-sm text-red-600 hover:text-red-800"
                        >
                            Выйти
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Окно чата -->
        <div class="p-4 sm:p-6">
            <!-- Хлебные крошки и информация о назначении -->
            <div class="mb-4">
                <router-link to="/chats" class="text-blue-500 hover:text-blue-700 text-sm flex items-center gap-1">
                    ← Назад к списку чатов
                </router-link>
                
                <!-- Информация о назначении -->
                <div v-if="chatInfo.is_assigned" class="mt-2 p-3 rounded" 
                    :class="chatInfo.is_mine ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200'">
                    <div v-if="chatInfo.is_mine" class="flex justify-between items-center">
                        <span class="text-green-700 text-sm">
                            ✅ Этот чат у вас в работе
                        </span>
                        <button 
                            @click="releaseChat"
                            class="text-sm bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded"
                        >
                            Освободить чат
                        </button>
                    </div>
                    <div v-else class="text-yellow-700 text-sm">
                        ⚠️ Этот чат взят в работу пользователем {{ chatInfo.assigned_user_name }}
                    </div>
                </div>
                
                <!-- Кнопка "Взять в работу" -->
                <div v-if="!chatInfo.is_assigned" class="mt-2">
                    <button 
                        @click="assignChat"
                        :disabled="assigning"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm"
                    >
                        {{ assigning ? 'Назначение...' : '🔒 Взять в работу' }}
                    </button>
                </div>
            </div>

            <h2 class="text-lg sm:text-xl font-bold mb-4">
                Чат ID: {{ chatId }}
            </h2>

            <!-- Форма отправки (только если чат свободен или назначен пользователю) -->
            <div v-if="canReply" class="mb-6 p-3 sm:p-4 border rounded shadow-sm">
                <textarea v-model="newMessage" placeholder="Ваше сообщение..." 
                    class="border p-2 w-full rounded text-sm sm:text-base" rows="3"
                    :disabled="!canReply"></textarea>
                <button @click="sendMessage" :disabled="sending || !canReply" 
                    class="mt-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm sm:text-base disabled:opacity-50">
                    {{ sending ? 'Отправка...' : 'Отправить' }}
                </button>
            </div>
            
            <div v-else class="mb-6 p-3 bg-gray-100 border rounded text-gray-500 text-sm">
                🔒 Вы не можете отвечать в этом чате. Чат уже взят в работу другим пользователем.
            </div>

            <!-- Сообщения -->
            <div v-if="loadingMessages && messages.length === 0" class="text-gray-500 text-sm">
                Загрузка сообщений...
            </div>

            <ul v-else-if="messages.length > 0" class="space-y-2">
                <li v-for="item in messages" :key="item.id" class="border-b pb-2 text-sm sm:text-base">
                    <div class="flex flex-col gap-1">
                        <div class="flex flex-wrap items-baseline gap-1">
                            <span :class="item.is_outbound ? 'text-green-600' : 'text-black'" class="font-semibold">
                                {{ item.user_name }}:
                            </span>
                            <span class="text-gray-700 break-words">{{ item.text || '[Нет текста]' }}</span>
                        </div>
                    </div>
                </li>
            </ul>

            <div v-else class="text-red-500 text-sm">Сообщений нет.</div>

            <div class="flex gap-2 mt-6">
                <button @click="fetchMessages" 
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded shadow text-sm sm:text-base">
                    Обновить сообщения
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../store/auth'


const props = defineProps({
    chatId: {
        type: String,
        required: true
    }
})

const router = useRouter()
const auth = useAuth()

const messages = ref([])
const loadingMessages = ref(false)
const newMessage = ref('')
const sending = ref(false)
const assigning = ref(false)
let refreshInterval = null

const chatInfo = ref({
    is_assigned: false,
    assigned_to: null,
    assigned_user_name: '',
    is_mine: false
})

const canReply = computed(() => {
    return !chatInfo.value.is_assigned || chatInfo.value.is_mine
})

// Загрузка сообщений
const fetchMessages = async () => {
    loadingMessages.value = true
    try {
        const response = await auth.apiClient.get(`/messages/${props.chatId}`)
        messages.value = response.data.messages
        chatInfo.value = {
            is_assigned: response.data.is_assigned,
            assigned_to: response.data.assigned_to,
            is_mine: response.data.is_mine,
            assigned_user_name: response.data.assigned_user_name || ''
        }
    } catch (error) {
        console.error("Ошибка при загрузке сообщений:", error)
        if (error.response?.status === 403) {
            chatInfo.value.is_assigned = true
            chatInfo.value.is_mine = false
        }
    } finally {
        loadingMessages.value = false
    }
}

// Запуск автообновления
const startAutoRefresh = () => {
    stopAutoRefresh()
    refreshInterval = setInterval(() => {
        fetchMessages()
    }, 5000)
}

// Остановка автообновления
const stopAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval)
        refreshInterval = null
    }
}

// Взять чат в работу
const assignChat = async () => {
    assigning.value = true
    try {
        const response = await auth.apiClient.post(`/chats/${props.chatId}/assign`)
        chatInfo.value = {
            is_assigned: true,
            assigned_to: auth.state.user?.id,
            is_mine: true,
            assigned_user_name: auth.state.user?.name
        }
        alert('Чат взят в работу!')
    } catch (error) {
        alert(error.response?.data?.message || 'Ошибка при назначении чата')
    } finally {
        assigning.value = false
    }
}

// Освободить чат
const releaseChat = async () => {
    if (!confirm('Вы уверены, что хотите освободить этот чат?')) return
    
    try {
        await auth.apiClient.post(`/chats/${props.chatId}/release`)
        chatInfo.value = {
            is_assigned: false,
            assigned_to: null,
            is_mine: false,
            assigned_user_name: ''
        }
        alert('Чат освобожден')
    } catch (error) {
        alert(error.response?.data?.message || 'Ошибка при освобождении чата')
    }
}

// Отправка сообщения
const sendMessage = async () => {
    if (!newMessage.value || !canReply.value) return
    
    sending.value = true
    try {
        await auth.apiClient.post('/send-message', {
            chat_id: props.chatId,
            text: newMessage.value
        })
        newMessage.value = ''
        fetchMessages() // Мгновенное обновление после отправки
    } catch (e) {
        alert('Ошибка при отправке')
    } finally {
        sending.value = false
    }
}

// Выход из системы
const handleLogout = async () => {
    stopAutoRefresh()
    await auth.logout()
    router.push('/login')
}


const markAsViewed = () => {
    try {
        const viewed = JSON.parse(localStorage.getItem('viewedChats') || '{}')
        viewed[props.chatId] = new Date().toISOString()
        localStorage.setItem('viewedChats', JSON.stringify(viewed))
    } catch (e) {
        console.error('Ошибка сохранения просмотра:', e)
    }
}


onMounted(() => {
    fetchMessages()
    startAutoRefresh()
    markAsViewed()
})

onUnmounted(() => {
    stopAutoRefresh()
})
</script>