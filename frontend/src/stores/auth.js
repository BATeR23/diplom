import { defineStore } from 'pinia'
import http from '@/helpers/http'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('auth_token'),
        status: 'idle',
        error: null,
        initialized: false,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token && !!state.user,
        isDriver: (state) => state.user?.role === 'driver',
        isAdmin: (state) => state.user?.role === 'admin',
        isManager: (state) => state.user?.role === 'manager' || state.user?.role === 'admin',
    },
    actions: {
        setToken(token) {
            this.token = token
            if (token) {
                localStorage.setItem('auth_token', token)
            } else {
                localStorage.removeItem('auth_token')
            }
        },
        setUser(user) {
            this.user = user
        },
        async bootstrap() {
            if (this.initialized || !this.token) {
                this.initialized = true
                return
            }

            try {
                const { data } = await http.get('/auth/me')
                this.user = data
            } catch (error) {
                this.setToken(null)
                this.user = null
            } finally {
                this.initialized = true
            }
        },
        async register(payload) {
            this.status = 'loading'
            this.error = null
            try {
                const { data } = await http.post('/auth/register', payload)
                this.setToken(data.token)
                this.user = data.user
                return data.user
            } catch (error) {
                this.error = error.response?.data?.message || 'Не удалось выполнить регистрацию'
                throw error
            } finally {
                this.status = 'idle'
            }
        },
        async login(payload) {
            this.status = 'loading'
            this.error = null
            try {
                const { data } = await http.post('/auth/login', payload)
                this.setToken(data.token)
                this.user = data.user
                return data.user
            } catch (error) {
                this.error = error.response?.data?.message || 'Неверные учетные данные'
                throw error
            } finally {
                this.status = 'idle'
            }
        },
        async logout() {
            try {
                await http.post('/auth/logout')
            } catch (_) {
                // ignore
            } finally {
                this.setToken(null)
                this.user = null
            }
        },
    },
})

