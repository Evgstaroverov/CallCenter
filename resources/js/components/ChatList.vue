<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Навигация -->
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <router-link to="/chats" class="text-xl font-bold text-gray-900">
                            Telegram Чаты
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

        <!-- Основной контент -->
        <div class="p-4 sm:p-6">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-xl sm:text-2xl font-bold">Чаты Telegram</h1>
                <div class="flex items-center gap-2 text-xs text-gray-400">
                    <span></span>
                </div>
            </div>

            <!-- Список чатов -->
            <div class="space-y-3">
                <div v-if="loading && chats.length === 0" class="text-gray-500 text-sm">
                    Загрузка чатов...
                </div>
                
                <router-link 
                    v-for="chat in chats" 
                    :key="chat.chat_id" 
                    :to="`/chats/${chat.chat_id}`"
                    class="block border rounded-lg p-3 sm:p-4 hover:bg-gray-50 transition-colors no-underline text-current bg-white"
                >
                    <div class="flex justify-between items-start">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-sm sm:text-base truncate">
                                    {{ chat.user_name || 'Чат ' + chat.chat_id }}
                                </h3>
                                
                                <!-- Индикатор назначения -->
                                <span v-if="chat.is_assigned" 
                                    class="text-xs px-2 py-0.5 rounded-full flex-shrink-0"
                                    :class="chat.is_mine ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">
                                    {{ chat.is_mine ? 'В работе' : 'Занят' }}
                                </span>
                                <span v-else class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full flex-shrink-0">
                                    Свободен
                                </span>
                            </div>
                            
                            <!-- Превью последнего сообщения -->
                            <p class="text-xs text-gray-500 truncate mb-1">
                                {{ chat.last_message_preview || 'Нет сообщений' }}
                            </p>
                            
                            <!-- Время последнего сообщения -->
                            <div class="text-xs text-gray-400">
                                <span v-if="getMessageTime(chat)">
                                    🕐 {{ formatMoscowTime(getMessageTime(chat)) }}
                                </span>
                                <span v-else class="text-gray-300">
                                    Нет сообщений
                                </span>
                            </div>
                        </div>
                    </div>
                </router-link>

                <div v-if="!loading && chats.length === 0" class="text-gray-500 text-sm">
                    Чатов пока нет
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '../store/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuth()

const chats = ref([])
const loading = ref(true)
const lastUpdate = ref('Никогда')
let refreshInterval = null

// Функция для получения времени сообщения
const getMessageTime = (chat) => {
    return chat.last_message_time || 
           chat.last_message_at || 
           chat.last_message_date || 
           chat.updated_at || 
           null
}

// Форматирование времени в московском часовом поясе
const formatMoscowTime = (dateString) => {
    if (!dateString) return ''
    
    try {
        // Создаем дату из строки (предполагаем, что сервер отдает время в UTC или с часовым поясом)
        const date = new Date(dateString)
        if (isNaN(date.getTime())) return ''
        
        // Получаем московское время, добавляя 3 часа к UTC
        const moscowOffset = 3 * 60 * 60 * 1000 // 3 часа в миллисекундах
        const utcTime = date.getTime()
        const moscowTime = new Date(utcTime + moscowOffset)
        
        // Текущее московское время
        const now = new Date()
        const moscowNow = new Date(now.getTime() + moscowOffset)
        
        // Сравниваем даты
        const today = new Date(moscowNow.getFullYear(), moscowNow.getMonth(), moscowNow.getDate())
        const messageDate = new Date(moscowTime.getFullYear(), moscowTime.getMonth(), moscowTime.getDate())
        
        const diffDays = Math.floor((today - messageDate) / (1000 * 60 * 60 * 24))
        
        const hours = moscowTime.getHours().toString().padStart(2, '0')
        const minutes = moscowTime.getMinutes().toString().padStart(2, '0')
        
        if (diffDays === 0) {
            return `Сегодня в ${hours}:${minutes} МСК`
        } else if (diffDays === 1) {
            return `Вчера в ${hours}:${minutes} МСК`
        } else {
            // Для старых дат показываем полную дату
            const day = moscowTime.getDate().toString().padStart(2, '0')
            const month = (moscowTime.getMonth() + 1).toString().padStart(2, '0')
            const year = moscowTime.getFullYear()
            return `${day}.${month}.${year} ${hours}:${minutes} МСК`
        }
    } catch (error) {
        console.error('Ошибка форматирования даты:', error)
        return ''
    }
}

// Загрузка списка чатов
const fetchChats = async () => {
    try {
        const timestamp = new Date().getTime()
        const response = await auth.apiClient.get(`/chats?_=${timestamp}`, {
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache',
                'Expires': '0',
            }
        })
        
        let newChats = []
        if (response.data && response.data.chats) {
            newChats = response.data.chats
        } else if (Array.isArray(response.data)) {
            newChats = response.data
        } else {
            return
        }
        
        chats.value = newChats
        
        // Время последнего обновления в московском времени
        const now = new Date()
        const moscowTime = new Date(now.getTime() + (3 * 60 * 60 * 1000))
        lastUpdate.value = moscowTime.toLocaleTimeString('ru-RU')
        
    } catch (error) {
        console.error("Ошибка при загрузке чатов:", error)
        if (error.response?.status === 401) {
            handleLogout()
        }
    } finally {
        loading.value = false
    }
}

// Запуск автообновления
const startAutoRefresh = () => {
    stopAutoRefresh()
    refreshInterval = setInterval(() => {
        fetchChats()
    }, 5000)
}

// Остановка автообновления
const stopAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval)
        refreshInterval = null
    }
}

// Выход из системы
const handleLogout = async () => {
    stopAutoRefresh()
    await auth.logout()
    router.push('/login')
}

// Следим за маршрутом
watch(() => route.path, (newPath, oldPath) => {
    if (newPath === '/chats') {
        fetchChats()
        startAutoRefresh()
    } else {
        stopAutoRefresh()
    }
})

onMounted(() => {
    fetchChats()
    startAutoRefresh()
})

onUnmounted(() => {
    stopAutoRefresh()
})
</script>