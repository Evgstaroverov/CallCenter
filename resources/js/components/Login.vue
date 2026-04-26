<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Вход в систему
                </h2>
            </div>
            <form @submit.prevent="handleLogin" class="mt-8 space-y-6">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">Email</label>
                        <input 
                            v-model="form.email"
                            id="email" 
                            type="email" 
                            required 
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                            placeholder="Email адрес"
                        >
                    </div>
                    <div>
                        <label for="password" class="sr-only">Пароль</label>
                        <input 
                            v-model="form.password"
                            id="password" 
                            type="password" 
                            required 
                            class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                            placeholder="Пароль"
                        >
                    </div>
                </div>

                <div v-if="error" class="text-red-500 text-sm text-center">
                    {{ error }}
                </div>

                <div>
                    <button 
                        type="submit" 
                        :disabled="loading"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                        {{ loading ? 'Вход...' : 'Войти' }}
                    </button>
                </div>

                <div class="text-center">
                    <router-link to="/register" class="text-blue-600 hover:text-blue-500 text-sm">
                        Нет аккаунта? Зарегистрироваться
                    </router-link>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../store/auth'

const router = useRouter()
const { login } = useAuth()

const form = reactive({
    email: '',
    password: ''
})

const error = ref('')
const loading = ref(false)

const handleLogin = async () => {
    error.value = ''
    loading.value = true
    
    const result = await login(form)
    
    if (result.success) {
        router.push('/chats') 
    } else {
        error.value = result.message
    }
    
    loading.value = false
}
</script>