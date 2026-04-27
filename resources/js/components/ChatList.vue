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

        <!-- Основной контент -->
        <div class="p-4 sm:p-6">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-xl sm:text-2xl font-bold">Новые чаты и взятые в работу</h1>
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
                            <p class="text-xs text-gray-500 truncate">
                                {{ chat.last_message_preview || 'Нет сообщений' }}
                            </p>
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
let refreshInterval = null

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