import { defineStore } from 'pinia'
import http from '@/helpers/http'

export const useRidesStore = defineStore('rides', {
    state: () => ({
        items: [],
        pagination: null,
        current: null,
        status: 'idle',
        error: null,
    }),
    actions: {
        async search(params = {}) {
            this.status = 'loading'
            this.error = null

            try {
                const { data } = await http.get('/rides/search', { params })
                this.items = data.data || data
                this.pagination = data.meta ? {
                    current_page: data.meta.current_page,
                    last_page: data.meta.last_page,
                    total: data.meta.total,
                } : null
            } catch (error) {
                this.error = error.response?.data?.message || 'Не удалось загрузить поездки'
                throw error
            } finally {
                this.status = 'idle'
            }
        },
        async fetchRide(id) {
            this.status = 'loading'
            this.error = null
            try {
                const { data } = await http.get(`/rides/${id}`)
                this.current = data
                return data
            } catch (error) {
                this.error = error.response?.data?.message || 'Не удалось загрузить поездку'
                throw error
            } finally {
                this.status = 'idle'
            }
        },
        resetCurrent() {
            this.current = null
        },
    },
})











