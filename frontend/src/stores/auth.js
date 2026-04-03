import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api.js'

export const useAuthStore = defineStore('auth', () => {
    const isAdmin = ref(false)
    const user = ref(null)
    const token = ref(localStorage.getItem('token') || '')

    async function login(username, password) {
        const res = await api.post('/api/auth/login', { username, password })
        if (res.data && res.data.token) {
            token.value = res.data.token
            localStorage.setItem('token', res.data.token)
            user.value = res.data.user
            isAdmin.value = true
        }
        return res.data
    }

    async function logout() {
        try {
            await api.post('/api/auth/logout')
        } catch (e) { }
        token.value = ''
        localStorage.removeItem('token')
        user.value = null
        isAdmin.value = false
    }

    async function checkAuth() {
        const saved = localStorage.getItem('token')
        if (!saved) return false
        try {
            const res = await api.get('/api/auth/me')
            user.value = res.data
            isAdmin.value = true
            token.value = saved
            return true
        } catch (e) {
            localStorage.removeItem('token')
            token.value = ''
            user.value = null
            isAdmin.value = false
            return false
        }
    }

    function avatarUrl(name) {
        return 'https://ui-avatars.com/api/?name=' + encodeURIComponent(name || 'A') + '&background=131B2A&color=8A2BE2&bold=true&size=80'
    }

    return { isAdmin, user, token, login, logout, checkAuth, avatarUrl }
})
