import { reactive } from 'vue'
import axios from 'axios'

const state = reactive({
    user: null,
    token: localStorage.getItem('token'),
    isAuthenticated: !!localStorage.getItem('token')
})

// Создаем apiClient
const apiClient = axios.create({
    baseURL: '/api',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
})

// Добавляем токен к запросам
apiClient.interceptors.request.use(config => {
    const token = localStorage.getItem('token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

// Экспортируем apiClient
export { apiClient }

// Экспортируем useAuth
export function useAuth() {
    const login = async (credentials) => {
        try {
            const response = await apiClient.post('/login', credentials)
            const { user, token } = response.data
            
            localStorage.setItem('token', token)
            state.user = user
            state.token = token
            state.isAuthenticated = true
            
            return { success: true }
        } catch (error) {
            return { 
                success: false, 
                message: error.response?.data?.message || 'Ошибка входа' 
            }
        }
    }

    const register = async (userData) => {
        try {
            const response = await apiClient.post('/register', userData)
            const { user, token } = response.data
            
            localStorage.setItem('token', token)
            state.user = user
            state.token = token
            state.isAuthenticated = true
            
            return { success: true }
        } catch (error) {
            return { 
                success: false, 
                message: error.response?.data?.message || 'Ошибка регистрации' 
            }
        }
    }

    const logout = async () => {
        try {
            await apiClient.post('/logout')
        } catch (error) {
            console.error('Logout error:', error)
        } finally {
            localStorage.removeItem('token')
            state.user = null
            state.token = null
            state.isAuthenticated = false
        }
    }

    const fetchUser = async () => {
        try {
            const response = await apiClient.get('/user')
            state.user = response.data
            state.isAuthenticated = true
        } catch (error) {
            localStorage.removeItem('token')
            state.user = null
            state.token = null
            state.isAuthenticated = false
        }
    }

    return {
        state,
        apiClient, // Возвращаем apiClient
        login,
        register,
        logout,
        fetchUser
    }
}