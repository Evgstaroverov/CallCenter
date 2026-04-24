<template>
    <!-- Добавлен max-w-xl для десктопа и min-h-screen для общего фона -->
    <div class="p-3 md:p-6 max-w-2xl mx-auto min-h-screen bg-white">
        
        <!-- Форма: убрали лишние отступы на мобилках -->
        <div class="mb-6 p-4 border rounded-lg shadow-sm bg-gray-50">
            <h2 class="text-sm font-semibold mb-3 text-gray-500 uppercase">Отправить ответ</h2>
            
            <input 
                v-model="chatId" 
                type="text" 
                placeholder="ID чата" 
                class="border p-3 mb-3 w-full rounded-md focus:ring-2 focus:ring-green-400 outline-none transition-all"
            >
            
            <textarea 
                v-model="newMessage" 
                rows="3"
                placeholder="Ваше сообщение..." 
                class="border p-3 w-full rounded-md focus:ring-2 focus:ring-green-400 outline-none transition-all"
            ></textarea>
            
            <!-- Кнопка на мобильных устройствах теперь во всю ширину -->
            <button 
                @click="sendMessage" 
                :disabled="sending" 
                class="mt-3 w-full md:w-auto bg-green-600 hover:bg-green-700 text-white font-medium px-6 py-3 rounded-md transition-colors disabled:opacity-50"
            >
                {{ sending ? 'Отправка...' : 'Отправить сообщение' }}
            </button>
        </div>

        <h1 class="text-xl md:text-2xl font-bold mb-4 px-1">Сообщения:</h1>

        <!-- Статус доступа -->
        <div v-if="isLocal" class="mx-1 p-2 bg-orange-50 border border-orange-200 rounded text-orange-700 mb-4 text-xs italic flex items-center">
            <span class="mr-2">⚠️</span> Telegram недоступен. Данные из базы.
        </div>

        <div v-if="loading" class="text-center py-10 text-gray-500">
            <div class="animate-pulse">Загрузка данных...</div>
        </div>

        <!-- Список сообщений: увеличены отступы для удобного тапа -->
        <ul v-else-if="messages.length > 0" class="space-y-3">
            <li v-for="item in messages" :key="item.id" 
                @click="chatId = item.chat_id"
                class="p-3 border rounded-lg active:bg-gray-100 md:hover:bg-gray-50 transition-colors cursor-pointer relative overflow-hidden"
                :class="item.is_outbound ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-blue-500'"
            >
                <div class="flex justify-between items-start mb-1">
                    <span :class="item.is_outbound ? 'text-green-700' : 'text-blue-700'" class="font-bold text-sm">
                        {{ item.user_name }}
                    </span>
                    <span v-if="isLocal" class="text-[9px] uppercase tracking-wider text-gray-400 font-mono">DB ONLY</span>
                </div>
                
                <p class="text-gray-800 leading-relaxed break-words">
                    {{ item.text || '[Пустое сообщение]' }}
                </p>
                
                <div class="text-[10px] text-gray-400 mt-2">
                    ID: {{ item.chat_id }}
                </div>
            </li>
        </ul>

        <div v-else class="text-center py-10 text-red-500 border-2 border-dashed rounded-xl">
            Сообщений пока нет.
        </div>

        <!-- Нижняя кнопка обновления -->
        <button 
            @click="fetchMessages" 
            class="mt-8 mb-10 w-full md:w-auto block mx-auto bg-blue-500 hover:bg-blue-600 text-white font-semibold px-8 py-3 rounded-full shadow-lg active:scale-95 transition-transform"
        >
            🔄 Обновить чаты
        </button>
    </div>
</template>
