import { createRouter, createWebHistory } from 'vue-router'
import Login from '../components/Login.vue'
import Register from '../components/Register.vue'
import ChatList from '../components/ChatList.vue'
import ChatView from '../components/ChatView.vue'

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { guest: true }
    },
    {
        path: '/register',
        name: 'Register',
        component: Register,
        meta: { guest: true }
    },
    {
        path: '/chats',              // ← Страница со списком чатов
        name: 'Chats',
        component: ChatList,
        meta: { requiresAuth: true }
    },
    {
        path: '/chats/:chatId',      // ← Конкретный чат
        name: 'Chat',
        component: ChatView,
        meta: { requiresAuth: true },
        props: true
    },
    {
        path: '/',                   // ← Главная перенаправляет на чаты
        redirect: '/chats'
    },
    {
        path: '/:pathMatch(.*)*',   // ← Несуществующие пути → на чаты
        redirect: '/chats'
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token')
    
    if (to.meta.requiresAuth && !token) {
        next('/login')
    } else if (to.meta.guest && token) {
        next('/chats')
    } else {
        next()
    }
})

export default router