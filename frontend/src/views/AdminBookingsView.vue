<template>
  <section class="page-section">
    <div class="page-section__content space-y-6">
      <span class="chip w-fit">Админ-панель</span>
      <h1 class="section-heading">Управление бронированиями</h1>

      <div class="surface-card p-6 space-y-4">
        <div class="flex gap-4">
          <select v-model="statusFilter" @change="fetchBookings" class="form-field-input">
            <option value="">Все статусы</option>
            <option value="pending">Ожидает</option>
            <option value="accepted">Принято</option>
            <option value="rejected">Отклонено</option>
            <option value="cancelled">Отменено</option>
            <option value="completed">Завершено</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="surface-card p-8 text-center text-gray-600">
        Загрузка...
      </div>

      <div v-else-if="bookings.length" class="space-y-4">
        <div
          v-for="booking in bookings"
          :key="booking.id"
          class="surface-card p-6 space-y-4"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-lg font-semibold text-gray-900">
                {{ booking.ride?.origin_city }} → {{ booking.ride?.destination_city }}
              </p>
              <p class="text-sm text-gray-700">
                Пассажир: {{ booking.passenger?.name }} · Водитель: {{ booking.ride?.driver?.name }}
              </p>
            </div>
            <div class="flex items-center gap-2">
              <select
                :value="booking.status"
                @change="updateBookingStatus(booking.id, $event.target.value)"
                class="form-field-input text-xs"
              >
                <option value="pending">Ожидает</option>
                <option value="accepted">Принято</option>
                <option value="rejected">Отклонено</option>
                <option value="cancelled">Отменено</option>
                <option value="completed">Завершено</option>
              </select>
              <button @click="deleteBooking(booking)" class="secondary-btn text-xs px-3 py-1 text-red-600 border-red-300 hover:bg-red-50">Удалить</button>
            </div>
          </div>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-700">
            <p>Мест: {{ booking.seats_requested }}</p>
            <p>Цена: {{ booking.price_total }} ₽</p>
            <p>Дата: {{ formatDate(booking.ride?.departure_time) }}</p>
            <p>Создано: {{ formatDate(booking.created_at) }}</p>
          </div>
        </div>
      </div>

      <div v-else class="surface-card p-8 text-gray-600 text-center">
        Бронирования не найдены
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import http from '@/helpers/http'
import dayjs from 'dayjs'

const loading = ref(false)
const bookings = ref([])
const statusFilter = ref('')

const fetchBookings = async () => {
  loading.value = true
  try {
    const params = {}
    if (statusFilter.value) params.status = statusFilter.value
    const { data } = await http.get('/admin/bookings', { params })
    bookings.value = data.data || data
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка загрузки бронирований')
  } finally {
    loading.value = false
  }
}

const updateBookingStatus = async (bookingId, status) => {
  try {
    await http.patch(`/admin/bookings/${bookingId}`, { status })
    await fetchBookings()
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка обновления статуса')
  }
}

const deleteBooking = async (booking) => {
  if (!confirm('Вы уверены, что хотите удалить это бронирование?')) return
  try {
    await http.delete(`/admin/bookings/${booking.id}`)
    await fetchBookings()
  } catch (error) {
    alert(error.response?.data?.message || 'Ошибка удаления бронирования')
  }
}

const formatDate = (value) => dayjs(value).format('D MMM YYYY, HH:mm')

onMounted(() => {
  fetchBookings()
})
</script>

