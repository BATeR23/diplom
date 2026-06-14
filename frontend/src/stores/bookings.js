import { defineStore } from 'pinia'
import http from '@/helpers/http'

export const useBookingsStore = defineStore('bookings', {
    state: () => ({
        asDriver: [],
        asPassenger: [],
        status: 'idle',
        error: null,
    }),
    actions: {
        async fetchDriverBookings() {
            this.status = 'loading'
            this.error = null
            try {
                const { data } = await http.get('/bookings', { params: { role: 'driver' } })
                this.asDriver = data.data || data
            } catch (error) {
                this.error = error.response?.data?.message || 'Не удалось загрузить заявки пассажиров'
                throw error
            } finally {
                this.status = 'idle'
            }
        },
        async fetchPassengerBookings() {
            this.status = 'loading'
            this.error = null
            try {
                const { data } = await http.get('/bookings', { params: { role: 'passenger' } })
                this.asPassenger = data.data || data
            } catch (error) {
                this.error = error.response?.data?.message || 'Не удалось загрузить мои бронирования'
                throw error
            } finally {
                this.status = 'idle'
            }
        },
        async updateStatus(bookingId, status) {
            await http.patch(`/bookings/${bookingId}`, { status })
            await Promise.all([this.fetchDriverBookings(), this.fetchPassengerBookings()])
        },
    },
})











